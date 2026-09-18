<?php

namespace Nodex\Nexus\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Nodex\Nexus\Dto\ModuleDtos\DefaultModuleConfigurationDto;

/**
 * Laravel-native counterpart to the nexus.validation.rules plugin filter,
 * fired at the exact same call sites right before it — deliberately
 * overlapping coverage, not a replacement for one another: a module's own
 * Listeners/ folder can react to this without a plugin class/#[Filter]
 * attribute at all, while a reusable cross-module plugin still uses the
 * filter. $rules is by reference, same as EntityCreating/TableDataPrepared.
 *
 *   class ExtendDemoRules {
 *       public function handle(GatheringValidationRules $event): void {
 *           if ($event->moduleConfig->name !== 'demo') return;
 *           $event->rules['title'][] = 'max:100';
 *       }
 *   }
 */
class GatheringValidationRules
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public DefaultModuleConfigurationDto $moduleConfig,
        public array &$rules,
        public string $action
    ) {
    }
}
