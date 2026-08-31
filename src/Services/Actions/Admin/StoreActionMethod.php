<?php

namespace Nodex\Nexus\Services\Actions\Admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Nodex\Nexus\Dto\ModuleDtos\DefaultModuleConfigurationDto;
use Nodex\Nexus\Enums\AdminButtonTypeEnum;
use Nodex\Nexus\Enums\AvailableActionEnum;
use Nodex\Nexus\Http\Actions\CallModuleHookAction;
use Nodex\Nexus\Http\Actions\GetModuleRequestAction;
use Nodex\Nexus\Services\Validation\NexusRuleCollector;

class StoreActionMethod
{
    public static function handle(FormRequest $request, DefaultModuleConfigurationDto $moduleConfig, ?string $id = null): RedirectResponse
    {
        return DB::transaction(function () use ($request, $moduleConfig) {
            //        TODO пододавати події
            //        EventDispatcher::dispatchModuleEvent($moduleConfig, 'before_update', $data);
            $modelClass = $moduleConfig->model;
            $saveType = request()->get("save", AdminButtonTypeEnum::SAVE->value);
            $moduleRequest = GetModuleRequestAction::getRequestByMethodName(AvailableActionEnum::STORE_ACTION->value, $moduleConfig);
            if ($moduleRequest && method_exists($moduleRequest, 'rules') && count($moduleRequest->rules()) > 0) {
                // A dedicated Request already validated (and, if it defines
                // withValidator(), cross-field-checked) this during its own
                // resolution above — untouched from before this collector existed.
                $validated = $moduleRequest->validated();
            } else {
                // D5: no dedicated Request (or an empty rules()) used to mean
                // request()->all() with zero validation. Now runs real,
                // field-config-derived validation instead.
                $rules = app(NexusRuleCollector::class)->collect($moduleConfig, AvailableActionEnum::STORE_ACTION->value, request()->all());
                $validated = validator(request()->all(), $rules)->validate();
            }
            $model = new $modelClass;

            event(new \Nodex\Nexus\Events\EntityCreating($model, $validated, $moduleConfig));

            CallModuleHookAction::hook(
                moduleConfig: $moduleConfig,
                callPosition: 'before',
                methodName: 'store',
                model: $model,
                newData: $validated
            );

            /**
             * @var Model $model
             */

            $model->fill(collect($validated)->only($model->getFillable())->toArray());
            $model->save();

            StoreRelationActionMethod::handle($model, $moduleConfig, $validated);

            event(new \Nodex\Nexus\Events\EntityCreated($model, $moduleConfig));

            CallModuleHookAction::hook(
                moduleConfig: $moduleConfig,
                callPosition: 'after',
                methodName: 'store',
                model: $model,
                newData: $validated,
                oldData: $model->attributesToArray()
            );

            $model->refresh();
            //        dd($validated, $model);
            //        unset($validated['relation']);
            //        $model->update($validated);
            //        $this->dispatchModuleEvent($module, 'update', $model);
            //        $this->dispatchModuleEvent($module, 'after_update', $model);

            if ($saveType == AdminButtonTypeEnum::SAVE->value) {
                return redirect()
                    ->route('nexus.module.action', ['module' => $moduleConfig->name, 'action' => 'edit', 'id' => $model->id ?? null])
                    ->with('success', 'Record updated successfully.')
                    ->with('alert_message', __('nexus::translate.alert.create_success'))->with('alert_type', 'success');
            } elseif ($saveType == AdminButtonTypeEnum::SAVE_AND_NEW->value) {
                return redirect()
                    ->route('nexus.module.action', ['module' => $moduleConfig->name, 'action' => 'create'])
                    ->with('success', 'Record updated successfully.')
                    ->with('alert_message', __('nexus::translate.alert.create_success'))->with('alert_type', 'success');
            } else {
                return redirect()->route("nexus.module.action", ['module' => $moduleConfig->name, 'action' => 'index'])->with('success', 'Record updated successfully.')
                        ->with('alert_message', __('nexus::translate.alert.create_success'))->with('alert_type', 'success');
            }
        });
    }
}
