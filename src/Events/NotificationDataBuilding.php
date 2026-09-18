<?php

namespace Nodex\Nexus\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

/**
 * Laravel-native counterpart to the nexus.notification.data plugin filter,
 * fired right before it in ModuleInstalledNotification::toArray() — lets a
 * module reword/localize/enrich the admin notification-bell payload without
 * a plugin class. $data is by reference, same {title, body} shape the
 * header dropdown's JS already expects.
 *
 *   class AddInstallTimestamp {
 *       public function handle(NotificationDataBuilding $event): void {
 *           $event->data['installedAt'] = now()->toIso8601String();
 *       }
 *   }
 */
class NotificationDataBuilding
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Notification $notification,
        public mixed $notifiable,
        public array &$data
    ) {
    }
}
