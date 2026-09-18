<?php

namespace Nodex\Nexus\Filters;

use Illuminate\Database\Eloquent\Builder;
use Nodex\Nexus\Enums\AdminAvailableFilterEnum;
use Nodex\Nexus\Services\SchemaColumnsCache;

class FilterHandler
{
    public function filter(Builder $query, string $filterName, string $value): Builder
    {
        switch ($filterName) {
            case AdminAvailableFilterEnum::FILTER_SEARCH->value:
                $query->where(function ($query) use ($value) {
                    $columns = SchemaColumnsCache::get($query->getModel()->getTable());
                    $grammar = $query->getQuery()->getGrammar();
                    foreach ($columns as $column) {
                        $query->orWhereRaw('LOWER('.$grammar->wrap($column).') LIKE ?', ['%'.strtolower($value).'%']);
                    }
                });
                break;
            case AdminAvailableFilterEnum::FILTER_TRASHED->value:
                if (in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses_recursive($query->getModel()))) {
                    if ($value == 'with') {
                        $query->withTrashed();
                    } elseif ($value == 'only') {
                        $query->onlyTrashed();
                    } else {
                        $query->withoutTrashed();
                    }
                }
                break;

        }

        return $query;
    }
}
