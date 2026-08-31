<?php

namespace Nodex\Nexus\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Nodex\Nexus\Attributes\Widget;
use Nodex\Nexus\Contracts\Widgets\ApiSerializable;
use Nodex\Nexus\Contracts\Widgets\WidgetInterface;
use Nodex\Nexus\Dto\Widgets\WidgetContext;
use Nodex\Nexus\Enums\WidgetSurface;
use Nodex\Nexus\Services\Widgets\WidgetOutputCache;
use Nodex\Nexus\Services\Widgets\WidgetPermissionChecker;
use Nodex\Nexus\Services\Widgets\WidgetRegistry;

/**
 * The Api surface, built entirely on top of WidgetRegistry + WidgetInterface
 * — see the Widget attribute's docblock and WidgetInterface's docblock on
 * why this needed no separate data layer of its own.
 *
 * Only index()/show() exist so far. /widgets/position/{position} (headless
 * @position) and /widgets/dashboard are deliberately not here yet: both need
 * a placement store (widget_assignments / nexus_dashboard_layouts) that
 * doesn't exist until Phase 5.3/5.6 — building them now would mean either a
 * permanently-empty stub or throwaway storage.
 */
class WidgetApiController extends Controller
{
    public function __construct(
        private WidgetRegistry $registry,
        private WidgetOutputCache $outputCache,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $entries = $this->visibleEntries($request);

        $data = array_values(array_map(
            fn (array $entry) => $this->describe($entry['meta']),
            $entries,
        ));

        return response()->json(['data' => $data]);
    }

    public function show(Request $request, string $key): JsonResponse
    {
        $entry = $this->registry->find($key);

        if (!$entry || !in_array(WidgetSurface::Api, $entry['meta']->surfaces, true)) {
            return response()->json(['message' => 'Widget not found.'], 404);
        }

        // Anonymous requests must not even learn a non-public widget exists.
        if (!$entry['meta']->apiPublic && !Auth::check()) {
            return response()->json(['message' => 'Widget not found.'], 404);
        }

        // Same non-disclosure rule as apiPublic above: a permission-gated
        // widget a signed-in user can't see must 404, not 403.
        if (!WidgetPermissionChecker::check($entry['meta'], Auth::user())) {
            return response()->json(['message' => 'Widget not found.'], 404);
        }

        $context = $this->contextFor($request);

        $data = $this->outputCache->remember(
            $entry['meta'],
            'api:show:' . md5(json_encode($context->params)) . ':' . ($context->locale ?? 'default') . ':' . (Auth::id() ?? 'guest'),
            function () use ($entry, $context, $key) {
                /** @var WidgetInterface $instance */
                $instance = app($entry['class']);
                $config = [];

                $raw = $instance instanceof ApiSerializable
                    ? $instance->toApiPayload($config, $context)
                    : $instance->getData($config, $context);

                return nexus_filter("widget.data.{$key}", $raw, $context);
            },
        );

        return response()->json([
            'data' => $this->describe($entry['meta']) + ['data' => $data],
        ]);
    }

    /**
     * @return array<int, array{class: string, meta: Widget}>
     */
    private function visibleEntries(Request $request): array
    {
        $entries = $this->registry->forSurface(WidgetSurface::Api);
        $user = Auth::user();

        $entries = array_filter($entries, fn (array $entry) => WidgetPermissionChecker::check($entry['meta'], $user));

        if (Auth::check()) {
            return array_values($entries);
        }

        return array_values(array_filter($entries, fn (array $entry) => $entry['meta']->apiPublic));
    }

    private function describe(Widget $meta): array
    {
        return [
            'key' => $meta->name,
            'label' => $meta->label,
            'icon' => $meta->icon,
            'group' => $meta->group,
            'defaultSize' => $meta->defaultSize,
        ];
    }

    private function contextFor(Request $request): WidgetContext
    {
        return new WidgetContext(
            surface: WidgetSurface::Api,
            user: Auth::user(),
            locale: app()->getLocale(),
            params: $request->query(),
        );
    }
}
