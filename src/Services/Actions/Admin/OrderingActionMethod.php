<?php

namespace Nodex\Nexus\Services\Actions\Admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Nodex\Nexus\Dto\ModuleDtos\DefaultModuleConfigurationDto;

class OrderingActionMethod
{
    public static function handle(FormRequest $request, DefaultModuleConfigurationDto $moduleConfig, ?string $id = null): \Illuminate\Http\RedirectResponse
    {
        return DB::transaction(function () use ($request, $moduleConfig, $id) {
            $modelClass = $moduleConfig->model;
            /**
             * @var Model $model
             */
            $model = $modelClass::findOrFail($id);
            $ordering = $request->get('ordering', 'up');
            $level = $request->get('level', 1);
            $result = match ($ordering) {
                'up' => $model->up($level),
                'down' => $model->down($level),
                default => 0,
            };

            return redirect()->route("nexus.module.action", ['module' => $moduleConfig->name, 'action' => 'index'])->with('success', 'Record updated successfully.');
        });
    }

}
