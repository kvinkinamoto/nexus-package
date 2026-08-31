<?php

namespace Nodex\Nexus\Notifications;

use Illuminate\Notifications\Notification;

/**
 * The first real dispatcher for the admin notification bell — see
 * Listeners/SendModuleInstalledNotification.php, fired on the already-existing
 * ModuleInstalled event. 'data' shape (title/body) matches exactly what the
 * header dropdown's JS already expects (see layouts/header.blade.php).
 */
class ModuleInstalledNotification extends Notification
{
    public function __construct(public string $moduleName)
    {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => __('nexus::translate.notifications.module_installed_title'),
            'body' => __('nexus::translate.notifications.module_installed_body', ['module' => $this->moduleName]),
        ];
    }
}
