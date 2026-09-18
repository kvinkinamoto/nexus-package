<?php

namespace Nodex\Nexus\Services\Actions\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Nodex\Nexus\Dto\ModuleDtos\DefaultModuleConfigurationDto;
use Nodex\Nexus\Http\Actions\GetModuleRequestAction;
use Nodex\Nexus\Services\FormBuilder;
use function Nodex\Nexus\Services\Actions\redirect;

class GetAllPublishPaginateActionMethod
{
    public static function handle(FormRequest $request, DefaultModuleConfigurationDto $moduleConfig, ?string $id = null)
    {
        $recourseType = $request->get('recourse_type', 'default');
        $routeData = $request->route()->parameters();
        $perPage  = $request->get('per_page', 10);
        $columns  = $request->get('columns', ['*']);
        $page     = $request->get('page', 1);
        
        $moduleRequest = GetModuleRequestAction::getRequestByMethodName('edit', $moduleConfig);
        if ($moduleRequest) {
            $validated = $moduleRequest->validated();
        } else {
            $validated = $request->all();
        }

        $modelClass = $moduleConfig->model;

        $model = new $modelClass;
        $resource = $moduleConfig->methodResource['getAllPublishPaginate']['resources'][$recourseType] ?? null;
        $with = $moduleConfig->methodResource['getAllPublishPaginate']['with'] ?? null;

        if ($with && !is_array($with)){
            $array = get_object_vars($with);
            $with = array_keys($array);
        }

        $query = $model::query();
        FormBuilder::useDefaultOrderIfExist($model, $query);
        if ($with) {
            $query->with($with);
        }

        $data = $query->paginate($perPage, $columns, 'page', $page);

        if ($resource) {
            return $resource::collection($data);
        }
        return response()->json($data);
    }

}
