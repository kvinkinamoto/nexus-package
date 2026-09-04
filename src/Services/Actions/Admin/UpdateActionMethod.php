<?php

namespace Nodex\Nexus\Services\Actions\Admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Nodex\Nexus\Dto\ModuleDtos\DefaultModuleConfigurationDto;
use Nodex\Nexus\Enums\AdminButtonTypeEnum;
use Nodex\Nexus\Enums\AvailableActionEnum;
use Nodex\Nexus\Http\Actions\CallModuleHookAction;
use Nodex\Nexus\Http\Actions\GetModuleRequestAction;
use Nodex\Nexus\Services\Validation\NexusRuleCollector;

class UpdateActionMethod
{
    public static function handle(FormRequest $request, DefaultModuleConfigurationDto $moduleConfig, ?string $id = null): \Illuminate\Http\RedirectResponse
    {
        return DB::transaction(function () use ($request, $moduleConfig, $id) {
            //        TODO пододавати події
            //        EventDispatcher::dispatchModuleEvent($moduleConfig, 'before_update', $data);
            $saveType = request()->get("save", AdminButtonTypeEnum::SAVE->value);
            $modelClass = $moduleConfig->model;
            $moduleRequest = GetModuleRequestAction::getRequestByMethodName(AvailableActionEnum::UPDATE_ACTION->value, $moduleConfig);
            if ($moduleRequest && method_exists($moduleRequest, 'rules') && count($moduleRequest->rules()) > 0) {
                // A dedicated Request already validated (and, if it defines
                // withValidator(), cross-field-checked) this during its own
                // resolution above — untouched from before this collector existed.
                app(NexusRuleCollector::class)->assertRelationCoverage($moduleConfig, $moduleRequest->rules());
                $validated = $moduleRequest->validated();
            } else {
                // D5: no dedicated Request (or an empty rules()) used to mean
                // request()->all() with zero validation. Now runs real,
                // field-config-derived validation instead.
                $rules = app(NexusRuleCollector::class)->collect($moduleConfig, AvailableActionEnum::UPDATE_ACTION->value, request()->all());
                $validated = validator(request()->all(), $rules)->validate();
            }
            /**
             * @var Model $model
             */
            $model = $modelClass::findOrFail($id);
            $oldData = $model->attributesToArray();

            event(new \Nodex\Nexus\Events\EntityUpdating($model, $validated, $oldData, $moduleConfig));

            StoreRelationActionMethod::handle($model, $moduleConfig, $validated);

            CallModuleHookAction::hook(
                moduleConfig: $moduleConfig,
                callPosition: 'before',
                methodName: 'update',
                model: $model,
                newData: $validated,
                oldData: $oldData
            );
            //        $this->dispatchModuleEvent($module, 'update', $model);
            //        $this->dispatchModuleEvent($module, 'after_update', $model);

            $model->fill(collect($validated)->only($model->getFillable())->toArray());
            $model->save();

            event(new \Nodex\Nexus\Events\EntityUpdated($model, $moduleConfig));

            CallModuleHookAction::hook(
                moduleConfig: $moduleConfig,
                callPosition: 'after',
                methodName: 'update',
                model: $model,
                newData: $validated,
                oldData: $oldData
            );

            if ($saveType == AdminButtonTypeEnum::SAVE->value) {
                //            dd(route('nexus.module.action', [$module->name, $action->name, 'id'=>$item->id]));
                return redirect()
                    ->route('nexus.module.action', ['module' => $moduleConfig->name, 'action' => 'edit', 'id' => $model->id ?? null])
                    ->with('success', 'Record updated successfully.')
                    ->with('alert_message', __('nexus::translate.alert.update_success'))->with('alert_type', 'success');
            } elseif ($saveType == AdminButtonTypeEnum::SAVE_AND_NEW->value) {
                return redirect()
                    ->route('nexus.module.action', ['module' => $moduleConfig->name, 'action' => 'create'])
                    ->with('success', 'Record updated successfully.')
                    ->with('alert_message', __('nexus::translate.alert.update_success'))->with('alert_type', 'success');
            } else {
                return redirect()->route("nexus.module.action", ['module' => $moduleConfig->name, 'action' => 'index'])
                    ->with('success', 'Record updated successfully.')
                    ->with('alert_message', __('nexus::translate.alert.update_success'))->with('alert_type', 'success');
            }
        });
    }

}
