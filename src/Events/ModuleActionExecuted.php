<?php

namespace Nodex\Nexus\Events;

/**
 * Fired by NexusController::action() right after a TableAction or
 * TableGroupAction has finished executing. Read-only actions (index, edit
 * and create as GET form renders, view) are excluded — this marks state
 * changes, not page views.
 *
 * No listener is registered by default: dispatching an event with zero
 * listeners is a no-op in Laravel, so the admin panel behaves identically
 * whether or not a logging module is installed. ActivityLog listens for
 * this event to write an audit-trail entry — see
 * app/Nexus/Modules/ActivityLog/Listeners/LogModuleAction.php.
 */
class ModuleActionExecuted
{
    public function __construct(
        public readonly string $moduleName,
        public readonly string $actionName,
        public readonly ?string $id = null,
        public readonly ?array $ids = null,
    ) {}
}
