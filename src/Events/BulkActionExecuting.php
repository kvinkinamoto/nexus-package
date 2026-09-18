<?php

namespace Nodex\Nexus\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Laravel-native counterpart to the nexus.bulk_action.executing plugin
 * filter, fired right before it in ModuleTable::runGroupAction() — before
 * the sync/async threshold split, so it applies uniformly whether the
 * selection runs inline or through Modules\BulkAction\Jobs\BulkActionJob.
 * $ids is by reference: a listener can drop specific ids from the batch
 * (a partial veto), or throw to abort the whole action (propagates as a
 * normal exception — there is no separate "cancel" flag, same as every
 * other *ing event in this package).
 *
 *   class BlockBulkDeleteForLockedDemos {
 *       public function handle(BulkActionExecuting $event): void {
 *           if ($event->actionName !== 'deleteGroup') return;
 *           $event->ids = array_values(array_diff($event->ids, LockedDemo::ids($event->ids)));
 *       }
 *   }
 */
class BulkActionExecuting
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $moduleName,
        public string $actionName,
        public array &$ids
    ) {
    }
}
