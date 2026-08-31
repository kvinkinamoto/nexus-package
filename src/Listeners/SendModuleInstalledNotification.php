<?php

namespace Nodex\Nexus\Listeners;

use Illuminate\Support\Facades\Notification;
use Nodex\Nexus\Enums\AdminPanelPermissionEnum;
use Nodex\Nexus\Events\ModuleInstalled;
use Nodex\Nexus\Notifications\ModuleInstalledNotification;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

/**
 * Registered directly in NexusServiceProvider::boot() (not module-scoped
 * discovery — ModuleInstalled fires for any module, not one module's own
 * lifecycle) rather than via the per-module Listeners/ convention.
 */
class SendModuleInstalledNotification
{
    public function handle(ModuleInstalled $event): void
    {
        $userModel = config('auth.providers.users.model');

        $recipients = $userModel::all()->filter(function ($user) {
            if (!method_exists($user, 'hasPermissionTo')) {
                return false;
            }

            try {
                return $user->hasPermissionTo(AdminPanelPermissionEnum::ALL->value);
            } catch (PermissionDoesNotExist) {
                return false;
            }
        });

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new ModuleInstalledNotification($event->moduleName));
    }
}
