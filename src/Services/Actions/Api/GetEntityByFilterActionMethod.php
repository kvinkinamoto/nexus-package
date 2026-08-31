<?php

namespace Nodex\Nexus\Services\Actions\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Nodex\Nexus\Dto\ModuleDtos\DefaultModuleConfigurationDto;
use Nodex\Nexus\Http\Actions\GetModuleRequestAction;
use Nodex\Nexus\Services\FormBuilder;
use function Nodex\Nexus\Services\Actions\redirect;

class GetEntityByFilterActionMethod
{
    public static function handle(
        FormRequest                   $request,
        DefaultModuleConfigurationDto $moduleConfig,
        ?string                        $id = null
    )
    {
        $filters = $request->get('filters', []);
        $filterLogic = strtolower($request->get('filter_logic', 'and'));
        $recourseType = $request->get('recourse_type', 'default');

        $modelClass = $moduleConfig->model;
        $model = new $modelClass;

        $query = $model::query();
        FormBuilder::useDefaultOrderIfExist($model, $query);

        $resource = $moduleConfig->methodResource['getEntityByFilter']['resources'][$recourseType] ?? null;
        $with = $moduleConfig->methodResource['getEntityByFilter']['with'] ?? null;

        if ($with && !is_array($with)){
            $array = get_object_vars($with);
            $with = array_keys($array);
        }

        if ($with) {
            $query->with($with);
        }

        /* filters */
        if (!empty($filters)) {
            $query->where(function ($q) use ($model, $filters, $filterLogic) {
                foreach ($filters as $filter) {
                    if (!isset($filter['field'], $filter['value'])) {
                        continue;
                    }

                    $locale = app()->getLocale();

                    $operator = $filter['operator'] ?? '=';
                    $method = $filterLogic === 'or' ? 'orWhere' : 'where';

                    if (
                        method_exists($model, 'isTranslatableAttribute')
                        && $model->isTranslatableAttribute($filter['field'])
                    ) {

                        $jsonField = "{$filter['field']}->{$locale}";

                        if ($operator === 'in' && is_array($filter['value'])) {
                            $q->{$method . 'In'}($jsonField, $filter['value']);
                        } else {
                            $q->{$method}($jsonField, $operator, $filter['value']);
                        }

                        continue;
                    }

                    if ($operator === 'in' && is_array($filter['value'])) {
                        $q->{$method . 'In'}($filter['field'], $filter['value']);
                    } else {
                        $q->{$method}(
                            $filter['field'],
                            $operator,
                            $filter['value']
                        );
                    }
                }
            });
        }

        $data = $query->firstOrFail();

        if ($resource) {
            return $resource::make($data);
        }

        return response()->json($data);
    }

}
