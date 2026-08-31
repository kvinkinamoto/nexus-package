<?php

namespace Nodex\Nexus\Services\Actions\Admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Nodex\Nexus\Dto\ModuleDtos\DefaultModuleConfigurationDto;
use Nodex\Nexus\Http\Actions\CallModuleHookAction;
use Nodex\Nexus\Http\Actions\GetModuleRequestAction;
use Nodex\Nexus\Requests\DefaultGroupActionRequest;

class PublishActionGroupMethod
{
    public static function handle(FormRequest $request, DefaultModuleConfigurationDto $moduleConfig): RedirectResponse
    {
        return self::handlePublish($request, $moduleConfig, true);
    }

    public static function handlePublish(FormRequest $request, DefaultModuleConfigurationDto $moduleConfig, bool $value): RedirectResponse
    {
        return DB::transaction(function () use ($request, $moduleConfig, $value) {

            $moduleRequest = GetModuleRequestAction::getRequestByMethodName('publishGroup', $moduleConfig);

            if ($moduleRequest) {
                $validated = $moduleRequest->validated();
            } else {
                $validated = app()->make(DefaultGroupActionRequest::class)->validated();
            }

            $modelClass = $moduleConfig->model;

            $query = $modelClass::query();

            if (in_array(SoftDeletes::class, class_uses_recursive($modelClass))) {
                $query->withTrashed();
            }

            $models = $query
                ->whereIn('id', $validated['items'])
                ->get();

            foreach ($models as $model) {

                $oldData = $model->attributesToArray();

                CallModuleHookAction::hook(
                    moduleConfig: $moduleConfig,
                    callPosition: 'before',
                    methodName: 'is_published',
                    model: $model,
                    newData: ['is_published' => $value],
                    oldData: $oldData
                );

                $model->update(['is_published' => $value]);

                CallModuleHookAction::hook(
                    moduleConfig: $moduleConfig,
                    callPosition: 'after',
                    methodName: 'is_published',
                    model: $model,
                    newData: ['is_published' => $value],
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
                ->with('success', __(
                    $value
                        ? 'Records published successfully'
                        : 'Records unpublished successfully'
                ))
                ->with('alert_message', __(
                    $value
                        ? 'nexus::translate.alert.bulk_publish_success'
                        : 'nexus::translate.alert.bulk_unpublish_success'
                ))
                ->with('alert_type', 'success');
        });
    }
}
