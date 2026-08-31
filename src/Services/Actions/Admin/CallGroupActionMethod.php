<?php

namespace Nodex\Nexus\Services\Actions\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Nodex\Nexus\Dto\ModuleDtos\ActionGroupConfigDto;
use Nodex\Nexus\Dto\ModuleDtos\DefaultModuleConfigurationDto;
use Nodex\Nexus\Http\Actions\CallModuleHookAction;
use Nodex\Nexus\Requests\DefaultGroupActionRequest;
use Nodex\Nexus\Http\Actions\GetModuleRequestAction;

/**
 * Generic handler for arbitrary group actions defined in the module config.
 *
 * Called when an action is declared via $table->group('myAction', 'label')
 * but has no dedicated controller method. Validates the selected IDs,
 * calls before/after hooks, and redirects back to the index.
 *
 * Modules can override any group action by defining a matching method in
 * their local AdminController (e.g. `myAction()`).
 */
class CallGroupActionMethod
{
    public static function handle(
        FormRequest $request,
        DefaultModuleConfigurationDto $moduleConfig,
        ActionGroupConfigDto $actionGroupConfig
    ): RedirectResponse {
        return DB::transaction(function () use ($request, $moduleConfig, $actionGroupConfig) {
            $actionName = $actionGroupConfig->name;

            // Allow modules to provide a custom request for validation
            $moduleRequest = GetModuleRequestAction::getRequestByMethodName($actionName, $moduleConfig);
            if ($moduleRequest) {
                $validated = $moduleRequest->validated();
            } else {
                $validated = app()->make(DefaultGroupActionRequest::class)->validated();
            }

            $ids = $validated['items'];

            CallModuleHookAction::hook(
                moduleConfig: $moduleConfig,
                callPosition: 'before',
                methodName: $actionName,
                newData: ['items' => $ids],
            );

            CallModuleHookAction::hook(
                moduleConfig: $moduleConfig,
                callPosition: 'after',
                methodName: $actionName,
                newData: ['items' => $ids],
            );

            return redirect()
                ->route('nexus.module.action', ['module' => $moduleConfig->name, 'action' => 'index'])
                ->with('success', 'Group action "' . $actionName . '" completed successfully.');
        });
    }
}
