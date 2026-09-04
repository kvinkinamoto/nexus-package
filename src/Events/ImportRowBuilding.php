<?php

namespace Nodex\Nexus\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Nodex\Nexus\Dto\ModuleDtos\DefaultModuleConfigurationDto;

/**
 * Laravel-native counterpart to the nexus.import.row plugin filter, fired
 * right before it in ImportActionMethod for every imported CSV row — a
 * module's own Listeners/ folder can transform a value (reformat a date,
 * map an external id) without a plugin class. $data is by reference,
 * fillable-filtered afterward either way — a listener can't inject a field
 * the model doesn't already allow mass assignment of.
 *
 *   class NormalizeDemoImportDates {
 *       public function handle(ImportRowBuilding $event): void {
 *           if ($event->moduleConfig->name !== 'demo' || !isset($event->data['event_date'])) return;
 *           $event->data['event_date'] = \Carbon\Carbon::parse($event->data['event_date'])->toDateString();
 *       }
 *   }
 */
class ImportRowBuilding
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public array &$data,
        public DefaultModuleConfigurationDto $moduleConfig
    ) {
    }
}
