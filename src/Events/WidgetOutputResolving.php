<?php

namespace Nodex\Nexus\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Nodex\Nexus\Dto\Widgets\WidgetContext;

/**
 * Laravel-native counterpart to the widget.{kind}.{key} plugin filter, fired
 * right before it — both from WidgetOutputCache::remember(), the one place
 * every widget consumer (dashboard, front @position(), the API) funnels
 * through. $kind is 'data' (a structured array) or 'html' (a rendered
 * string) — see WidgetOutputCache's own docblock. $output is by reference.
 *
 *   class MarkUsersCountOutput {
 *       public function handle(WidgetOutputResolving $event): void {
 *           if ($event->widgetKey !== 'usersCount' || $event->kind !== 'data') return;
 *           $event->output['fromListener'] = true;
 *       }
 *   }
 */
class WidgetOutputResolving
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $widgetKey,
        public string $kind,
        public mixed &$output,
        public WidgetContext $context
    ) {
    }
}
