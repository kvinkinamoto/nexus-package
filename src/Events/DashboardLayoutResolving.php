<?php

namespace Nodex\Nexus\Events;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Laravel-native counterpart to the nexus.dashboard.layout plugin filter,
 * fired right before it in DashboardLayoutResolver::resolveFor() — with
 * $layout already resolved through the normal own-row -> is_default-row ->
 * config('nexus.dashboard.default') waterfall. A module's own Listeners/
 * folder can override it per role/condition (e.g. a role-scoped default —
 * see that resolver's own docblock on why that tier doesn't exist yet)
 * without a plugin class. $layout is by reference.
 *
 *   class RoleScopedDashboardDefault {
 *       public function handle(DashboardLayoutResolving $event): void {
 *           if (!$event->user?->hasRole('support')) return;
 *           $event->layout = ['ticketsCount', 'usersCount'];
 *       }
 *   }
 */
class DashboardLayoutResolving
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public ?Authenticatable $user,
        public array &$layout
    ) {
    }
}
