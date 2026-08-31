<?php

namespace Nodex\Nexus\Services\Actions\Admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Nodex\Nexus\Dto\ModuleDtos\DefaultModuleConfigurationDto;
use Nodex\Nexus\Requests\DefaultGroupActionRequest;

class DuplicateActionGroupMethod
{
    public static function handle(FormRequest $request, DefaultModuleConfigurationDto $moduleConfig): RedirectResponse
    {
        return DB::transaction(function () use ($request, $moduleConfig) {
            $validated = app()->make(DefaultGroupActionRequest::class)->validated();
            $ids = $validated['items'] ?? [];

            $duplicatableRelations = $request->input('duplicatable_relations', []);
            $duplicateTree = $request->boolean('duplicate_tree', false);

            $modelClass = $moduleConfig->model;

            foreach ($ids as $id) {
                $originalModel = $modelClass::find($id);
                if ($originalModel) {
                    DuplicateActionMethod::duplicateModel($originalModel, $moduleConfig, $duplicatableRelations, $duplicateTree);
                }
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
                ->with('success', count($ids) . ' records duplicated successfully.')
                ->with('alert_message', __('nexus::translate.alert.duplicate_group_success'))->with('alert_type', 'success');
        });
    }
}
