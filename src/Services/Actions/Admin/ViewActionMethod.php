<?php

namespace Nodex\Nexus\Services\Actions\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Nodex\Nexus\Dto\ModuleDtos\DefaultModuleConfigurationDto;
use Nodex\Nexus\Services\FormBuilder;

/**
 * Read-only counterpart of EditActionMethod — renders the Infolist (view)
 * screen instead of an editable form. Reuses FormBuilder::build() so the
 * same $fields/$relatedData/$languages the edit form gets (including
 * anything injected via the AdminFormBuilding event, e.g. SEO fields) are
 * available for display, without any validation/request handling.
 */
class ViewActionMethod
{
    public static function handle(FormRequest $request, DefaultModuleConfigurationDto $moduleConfig, ?string $id = null)
    {
        $modelClass = $moduleConfig->model;

        $model = $modelClass::findOrFail($id);

        $form = FormBuilder::build($moduleConfig, $model);

        return view('nexus::' . config('nexus.template') . '.pages.view', [
            'module' => $moduleConfig,
            'formData' => $form,
            'model' => $model,
        ]);
    }
}
