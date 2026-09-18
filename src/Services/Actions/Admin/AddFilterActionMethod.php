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
         * Falls back to the base FilterHandler itself when a module has no
         * Filters/ModuleFilterHandler.php of its own — that base class
         * already implements the reserved 'search' (all-columns LIKE) and
         * 'trashed' filter names generically (see FilterHandler::filter()),
         * so every module gets those for free rather than only modules that
         * happen to have generated a (possibly empty) subclass via
         * `php artisan nexus:make:filter`. Previously this silently applied
         * NO filtering at all for such a module — its #[TableFilter(type:
         * 'search')] search box rendered and accepted input, but typing into
         * it had zero effect on the results, with nothing to indicate why.
         *
         * @var FilterHandler $filterModuleHandler
         */
        $filterModuleHandler = ModuleManager::getClassFromModule($module->name.'\\Filters\\ModuleFilterHandler')
            ?? new FilterHandler;

        foreach ($filters as $filterName => $filterValue) {
            if (! $filterValue) {
                continue;
            }
            $filterModuleHandler->filter($query, $filterName, $filterValue);
        }

        if (! empty($dynamicFilters)) {
            $query = UniversalFilterBuilder::apply($query, $dynamicFilters);
        }

        return $query;
    }
}
