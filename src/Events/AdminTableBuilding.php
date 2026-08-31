<?php

namespace Nodex\Nexus\Events;

use Nodex\Nexus\Dto\ModuleDtos\DefaultModuleConfigurationDto;

/**
 * Fired by TableBuilder just before a module's index table is assembled.
 *
 * Listeners may add columns, filters, or actions to the table
 * without modifying the module's own configuration.
 *
 * Example:
 *   class InjectStockColumn {
 *       public function handle(AdminTableBuilding $event): void {
 *           if ($event->moduleName !== 'shopProduct') return;
 *           $event->config->table()->column('stock', 'Залишок')->sortable();
 *       }
 *   }
 */
class AdminTableBuilding
{
    public function __construct(
        /** The name of the module whose table is being built (e.g. 'shopProduct') */
        public readonly string $moduleName,

        /**
         * The live DTO. Listeners may call $event->config->table()->column(...)
         * to add columns, or $event->config->table()->filter(...) to add filters.
         */
        public readonly DefaultModuleConfigurationDto $config,
    ) {}
}
