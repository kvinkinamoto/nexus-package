<?php

namespace Nodex\Nexus\Http\Actions;

use Illuminate\Support\Str;
use Nodex\Nexus\Dto\ModuleDtos\DefaultModuleConfigurationDto;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;

class CallModuleHookAction
{
    public static function hook(
        DefaultModuleConfigurationDto $moduleConfig, string $callPosition,
        string                        $methodName, ?Model $model = null, array $newData = [], array $oldData = []
    ): array
    {
        $rezData = [];

        $fullClass = 'App\\Nexus\\Modules\\' . Str::ucfirst($moduleConfig->name) . '\\Hooks\\' . ucfirst($methodName);

        if (class_exists($fullClass)) {
            $hookObj = app()->make($fullClass);
            if ($hookObj) {
                $rezData = $hookObj->$callPosition($model, $newData, $oldData);
            }
        }

        return $rezData;
    }
}
