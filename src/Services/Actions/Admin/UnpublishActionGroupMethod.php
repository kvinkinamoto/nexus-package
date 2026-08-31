<?php

namespace Nodex\Nexus\Services\Actions\Admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Nodex\Nexus\Dto\ModuleDtos\DefaultModuleConfigurationDto;

class UnpublishActionGroupMethod
{

    public static function handle(FormRequest $request, DefaultModuleConfigurationDto $moduleConfig): RedirectResponse
    {
        return PublishActionGroupMethod::handlePublish($request, $moduleConfig, false);
    }
}
