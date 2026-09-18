<?php

namespace Nodex\Nexus\Services\Actions\Admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Nodex\Nexus\Dto\ModuleDtos\DefaultModuleConfigurationDto;
use Nodex\Nexus\Http\Actions\CallModuleHookAction;
use Nodex\Nexus\Http\Actions\GetModuleRequestAction;

class RestoreActionMethod
{
    public static function handle(FormRequest $request, DefaultModuleConfigurationDto $moduleConfig, ?string $id = null): RedirectResponse
    {
        return DB::transaction(function () use ($request, $moduleConfig, $id) {
            //        EventDispatcher::dispatchModuleEvent($moduleConfig, 'before_update', $data);
            $modelClass = $moduleConfig->model;
            /**
             * @var Model $modelClass
             */
            $query = $modelClass::query();
            if (in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses_recursive($modelClass))) {
                $query->withTrashed();
            }
            $model = $query->where("id", $id)->firstOrFail();

            $moduleRequest = GetModuleRequestAction::getRequestByMethodName('restore', $moduleConfig);
            $validated = [];
            if ($moduleRequest) {
                $validated = $moduleRequest->validated();
            }
            $oldData = $model->attributesToArray();
            CallModuleHookAction::hook(
                moduleConfig: $moduleConfig,
                callPosition: 'before',
                methodName: 'restore',
                model: $model,
                newData: $validated,
                oldData: $oldData
            );

            $model->restore();

            CallModuleHookAction::hook(
                moduleConfig: $moduleConfig,
                callPosition: 'after',
                methodName: 'restore',
                model: $model,
                newData: $validated,
                oldData: $oldData
            );

            //        $this->dispatchModuleEvent($module, 'update', $record);
            //        $this->dispatchModuleEvent($module, 'after_update', $record);

            $redirectQueryString = $request->input('redirect_query');

            $routeParameters = [
                'module' => $moduleConfig->name,
                'action' => 'index'
            ];

            if ($redirectQueryString) {
                parse_str($redirectQueryString, $parsedQuery);
                $routeParameters = array_merge($parsedQuery, $routeParameters);
            }

            return redirect()->route("nexus.module.action", $routeParameters)
                ->with('success', 'Record restore successfully.');
        });
    }
}
