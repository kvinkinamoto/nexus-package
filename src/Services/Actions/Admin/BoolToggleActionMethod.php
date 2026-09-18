<?php

namespace Nodex\Nexus\Services\Actions\Admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Nodex\Nexus\Dto\ModuleDtos\DefaultModuleConfigurationDto;
use Nodex\Nexus\Http\Actions\CallModuleHookAction;
use Nodex\Nexus\Http\Actions\GetModuleRequestAction;

class BoolToggleActionMethod
{
    public static function handle(FormRequest $request, DefaultModuleConfigurationDto $moduleConfig, ?string $id = null): RedirectResponse
    {
        return DB::transaction(function () use ($request, $moduleConfig, $id) {
            //        EventDispatcher::dispatchModuleEvent($moduleConfig, 'before_update', $data);
            $modelClass = $moduleConfig->model;
            $validated = [];
            $moduleRequest = GetModuleRequestAction::getRequestByMethodName('boolToggle', $moduleConfig);
            if ($moduleRequest) {
                $validated = $moduleRequest->validated();
            }
            /**
             * @var Model $modelClass
             */
            $fieldName = $request->get('fieldName');
            if ($fieldName) {
                $model = $modelClass::query()->findOrFail($id);

                $oldData = $model->attributesToArray();
                CallModuleHookAction::hook(
                    moduleConfig: $moduleConfig,
                    callPosition: 'before',
                    methodName: 'boolToggle',
                    model: $model,
                    newData: $validated,
                    oldData: $oldData
                );

                switch ($fieldName) {
                    case 'is_default':
                    case 'default':
                        $modelClass::query()->update([$fieldName => 0]);

                        $modelClass::query()
                            ->where('id', $id)
                            ->update([$fieldName => 1]);

                        break;
                    default:
                        $model->{$fieldName} = !$model->{$fieldName};
                        break;
                }
                $model->save();

                CallModuleHookAction::hook(
                    moduleConfig: $moduleConfig,
                    callPosition: 'after',
                    methodName: 'boolToggle',
                    model: $model,
                    newData: $validated,
                    oldData: $oldData
                );

            }
            return redirect()->route("nexus.module.action", ['module' => $moduleConfig->name, 'action' => 'index'])->with('success', 'Record updated successfully.')
                ->with('alert_message', __('nexus::translate.alert.update_success'))->with('alert_type', 'success');
        });
    }


}
