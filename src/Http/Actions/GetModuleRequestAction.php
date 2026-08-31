<?php

namespace Nodex\Nexus\Http\Actions;

use Illuminate\Foundation\Http\FormRequest;
use Nodex\Nexus\Dto\ModuleDtos\DefaultModuleConfigurationDto;

class GetModuleRequestAction
{
    public static function getRequestByMethodName(string $methodName, DefaultModuleConfigurationDto $moduleConfiguration): ?FormRequest
    {
        if (isset($moduleConfiguration->methodRequests[$methodName])) {
            return app($moduleConfiguration->methodRequests[$methodName]);
        }
        return null;
//        throw new \Exception("Request for method '{$methodName}' not found in configuration");
    }
}
