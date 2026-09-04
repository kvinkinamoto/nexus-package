<?php

namespace Nodex\Nexus\Services\Actions\Admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Nodex\Nexus\Dto\ModuleDtos\DefaultModuleConfigurationDto;
use Nodex\Nexus\Events\EntityRestored;
use Nodex\Nexus\Events\EntityRestoring;
use Nodex\Nexus\Http\Actions\CallModuleHookAction;
use Nodex\Nexus\Http\Actions\GetModuleRequestAction;
use Nodex\Nexus\Requests\DefaultGroupActionRequest;

class RestoreActionGroupMethod
{
    public static function handle(FormRequest $request, DefaultModuleConfigurationDto $moduleConfig): RedirectResponse
    {
        return DB::transaction(function () use ($request, $moduleConfig) {

            $moduleRequest = GetModuleRequestAction::getRequestByMethodName('restoreGroup', $moduleConfig);

            if ($moduleRequest) {
                $validated = $moduleRequest->validated();
            } else {
                $validated = app()->make(DefaultGroupActionRequest::class)->validated();
            }

            $modelClass = $moduleConfig->model;

            if (!in_array(SoftDeletes::class, class_uses_recursive($modelClass))) {
                return back()
                    ->with('error', 'Model does not support restore')
                    ->with('alert_message', __('nexus::translate.bulk_restore_not_supported'))
                    ->with('alert_type', 'warning');
            }

            $models = $modelClass::query()
                ->onlyTrashed()
                ->whereIn('id', $validated['items'])
                ->get();

            foreach ($models as $model) {

                $oldData = $model->attributesToArray();

                event(new EntityRestoring($model, $moduleConfig));
                nexus_action('nexus.entity.restoring', $model, $moduleConfig);
                CallModuleHookAction::hook(
                    moduleConfig: $moduleConfig,
                    callPosition: 'before',
                    methodName: 'restore',
                    model: $model,
                    newData: $validated,
                    oldData: $oldData
                );

                $model->restore();

                event(new EntityRestored($model, $moduleConfig));
                nexus_action('nexus.entity.restored', $model, $moduleConfig);
                CallModuleHookAction::hook(
                    moduleConfig: $moduleConfig,
                    callPosition: 'after',
                    methodName: 'restore',
                    model: $model,
                    newData: $validated,
                    oldData: $oldData
                );
            }

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
                ->with('success', 'Records restored successfully')
                ->with('alert_message', __('nexus::translate.alert.bulk_restore_success'))
                ->with('alert_type', 'success');
        });
    }
}
