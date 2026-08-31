<?php

namespace Nodex\Nexus\Services\Actions\Admin;

use Illuminate\Database\Eloquent\Builder;
use Nodex\Nexus\Filters\FilterHandler;
use Nodex\Nexus\Models\Module;
use Nodex\Nexus\Services\ModuleManager;
use Nodex\Nexus\Services\QueryBuilders\UniversalFilterBuilder;

class AddFilterActionMethod
{
    public static function handle(Builder $query, array $filters, Module $module, ?int $userId = null, array $dynamicFilters = []): Builder
    {
        /**
         * @var FilterHandler $filterModuleHandler
         */
        $filterModuleHandler = ModuleManager::getClassFromModule($module->name . '\\Filters\\ModuleFilterHandler');

        if ($filterModuleHandler !== null) {
            foreach ($filters as $filterName => $filterValue) {
                if (!$filterValue) {
                    continue;
                }
                $filterModuleHandler->filter($query, $filterName, $filterValue);
            }
        }

        if (!empty($dynamicFilters)) {
            $query = UniversalFilterBuilder::apply($query, $dynamicFilters);
        }

        return $query;
    }
}
