<?php

namespace Nodex\Nexus\Services\Actions\Admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Nodex\Nexus\Dto\ModuleDtos\DefaultModuleConfigurationDto;
use Nodex\Nexus\Http\Actions\CallModuleHookAction;
use Nodex\Nexus\Http\Actions\GetModuleRequestAction;
use Nodex\Nexus\Requests\DefaultGroupActionRequest;
use Nodex\Nexus\Events\EntityDeleted;
use Nodex\Nexus\Events\EntityDeleting;

class DeleteActionGroupMethod
{
    public static function handle(FormRequest $request, DefaultModuleConfigurationDto $moduleConfig): RedirectResponse
    {
        return DB::transaction(function () use ($request, $moduleConfig) {

            $moduleRequest = GetModuleRequestAction::getRequestByMethodName('deleteGroup', $moduleConfig);

            if ($moduleRequest) {
                $validated = $moduleRequest->validated();
            } else {
                $validated = app()->make(DefaultGroupActionRequest::class)->validated();
            }

            $modelClass = $moduleConfig->model;

            $models = $modelClass::query()
                ->whereIn('id', $validated['items'])
                ->get();

            foreach ($models as $model) {

                $oldData = $model->attributesToArray();

                event(new EntityDeleting($model, $moduleConfig));
                nexus_action('nexus.entity.deleting', $model, $moduleConfig);

                CallModuleHookAction::hook(
                    moduleConfig: $moduleConfig,
                    callPosition: 'before',
                    methodName: 'delete',
                    model: $model,
                    newData: $validated,
                    oldData: $oldData
                );

                $model->delete();

                event(new EntityDeleted($model, $moduleConfig));
                nexus_action('nexus.entity.deleted', $model, $moduleConfig);

                CallModuleHookAction::hook(
                    moduleConfig: $moduleConfig,
                    callPosition: 'after',
                    methodName: 'delete',
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
                ->with('success', 'Records deleted successfully')
                ->with('alert_message', __('nexus::translate.alert.bulk_delete_success'))
                ->with('alert_type', 'success');
        });
    }
}
