<?php

namespace Nodex\Nexus\Services\Actions\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Nodex\Nexus\Dto\ModuleDtos\DefaultModuleConfigurationDto;
use Nodex\Nexus\Http\Actions\GetModuleRequestAction;
use Nodex\Nexus\Services\FormBuilder;
use function Nodex\Nexus\Services\Actions\redirect;
use Kalnoy\Nestedset\NodeTrait;

class GetAllPublishActionMethod
{
    public static function handle(FormRequest $request, DefaultModuleConfigurationDto $moduleConfig, ?string $id = null)
    {
        $recourseType = $request->get('recourse_type', 'default');
        $take = $request->get('take', null);
        $tree = (bool)$request->get('tree', false);
        $routeData = $request->route()->parameters();
        $moduleRequest = GetModuleRequestAction::getRequestByMethodName('edit', $moduleConfig);
        if ($moduleRequest) {
            $validated = $moduleRequest->validated();
        } else {
            $validated = $request->all();
        }

        $modelClass = $moduleConfig->model;

        $model = new $modelClass;
        $resource = $moduleConfig->methodResource['getAllPublish']['resources'][$recourseType] ?? null;
        $with = $moduleConfig->methodResource['getAllPublish']['with'] ?? null;

        if ($with && !is_array($with)){
            $array = get_object_vars($with);
            $with = array_keys($array);
        }

        $query = $model::query();
        FormBuilder::useDefaultOrderIfExist($model, $query);
        if ($with) {
            $query->with($with);
        }

        if (!$take) {
            $data = $query->where('is_published', 1)->get();
        }else{
            $data = $query->where('is_published', 1)->take($take)->get();
        }

        if ($tree && in_array(NodeTrait::class, class_uses_recursive($model))) {
            $data = $data->toTree();
        }

        if ($resource) {
            return $resource::collection($data);
        }
        return response()->json($data);
    }

}
