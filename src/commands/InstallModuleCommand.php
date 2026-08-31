<?php

namespace Nodex\Nexus\commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Nodex\Nexus\Services\ModuleManager;

class InstallModuleCommand extends Command
{
    protected $signature = 'nexus:module:install {name?}';
    protected $description = 'Install a module';

    public function handle(ModuleManager $moduleManager)
    {
        $module = Str::ucfirst($this->argument('name'));
        if ($module) {
            $moduleManager->install($module);
            $this->info("Module {$module} installed successfully");
        } else {
            $modules = $moduleManager->getModules();
            foreach ($modules as $mod) {
                $moduleManager->install($mod['name'], $this->output);
                $this->info("Module {$mod['name']} installed successfully");
            }
            return;
        }
    }
}
