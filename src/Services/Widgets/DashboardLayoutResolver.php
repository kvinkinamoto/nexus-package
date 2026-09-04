<?php

namespace Nodex\Nexus\Services\Widgets;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Nodex\Nexus\Events\DashboardLayoutResolving;

/**
 * Resolves the ordered list of widget keys for a user's admin dashboard.
 * Priority: the user's own nexus_dashboard_layouts row -> the single
 * is_default=true row -> config('nexus.dashboard.default') -> [].
 *
 * The `role` column exists on the table for a future role-scoped fallback
 * tier between "own row" and "is_default row", but isn't consulted yet —
 * this codebase has no confirmed single source for "the current user's role
 * name" (see the migration's docblock). Wiring that in is a follow-up, not a
 * silent gap: a request for a role-specific default would fall through to
 * is_default/config today, never to a 404 or empty dashboard for no reason.
 */
class DashboardLayoutResolver
{
    /**
     * @return string[] Ordered widget keys.
     */
    public function resolveFor(?Authenticatable $user): array
    {
        $layout = null;

        if ($user) {
            $ownLayout = DB::table('nexus_dashboard_layouts')
                ->where('user_id', $user->getAuthIdentifier())
                ->value('layout');

            if ($ownLayout !== null) {
                $layout = json_decode($ownLayout, true) ?? [];
            }
        }

        if ($layout === null) {
            $defaultLayout = DB::table('nexus_dashboard_layouts')
                ->where('is_default', true)
                ->value('layout');

            if ($defaultLayout !== null) {
                $layout = json_decode($defaultLayout, true) ?? [];
            }
        }

        if ($layout === null) {
            $layout = config('nexus.dashboard.default', []);
        }

        // Lets a plugin override the resolved default entirely (e.g. a
        // role-scoped default — see this class's own docblock on why that
        // tier doesn't exist as a first-class waterfall step yet).
        event(new DashboardLayoutResolving($user, $layout));

        return nexus_filter('nexus.dashboard.layout', $layout, $user);
    }

    public function saveFor(Authenticatable $user, array $widgetKeys): void
    {
        DB::table('nexus_dashboard_layouts')->updateOrInsert(
            ['user_id' => $user->getAuthIdentifier()],
            ['layout' => json_encode(array_values($widgetKeys)), 'updated_at' => now()],
        );
    }
}
