<?php

namespace Nodex\Nexus\commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Nodex\Nexus\Enums\AdminPanelPermissionEnum;
use App\Nexus\Modules\Permission\Models\Permission;

class InitPermissionCommand extends Command
{
    protected $signature = 'nexus:permission:init';
    protected $description = 'Install permissions';

    public function handle()
    {
        $defaultPermissions = AdminPanelPermissionEnum::cases();

        foreach ($defaultPermissions as $permission) {
            $displayName = Str::of($permission->value)->replace('_', ' ')->lower()->ucfirst()->value();
//            dd($permission->value,$displayName);

            Permission::updateOrCreate(
                ['name' => $permission->value],
                [
                    'display_name' => [
                        'en' => $displayName
                    ]
                ]
            );

        }
    }
}
