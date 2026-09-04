<?php

namespace Nodex\Nexus\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Nodex\Nexus\Models\Module;

/**
 * Laravel-native counterpart to the nexus.permission.check plugin filter,
 * fired right before it in CheckUserPermissionAction::handle() — a module's
 * own Listeners/ folder can override or extend a permission decision
 * without a plugin class. $result is by reference and already holds the
 * normally-computed decision when this fires.
 *
 *   class GrantSupportStaffReadAccess {
 *       public function handle(PermissionChecking $event): void {
 *           if ($event->action !== 'index') return;
 *           if (auth()->user()?->hasRole('support')) $event->result = true;
 *       }
 *   }
 */
class PermissionChecking
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $action,
        public ?Module $module,
        public string $place,
        public bool &$result
    ) {
    }
}
