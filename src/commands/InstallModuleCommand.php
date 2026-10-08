<?php

namespace Nodex\Nexus\commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Nodex\Nexus\Services\ModuleManager;

class InstallModuleCommand extends Command
{
    protected $signature = 'nexus:module:install {name?}';
    protected $description = 'Install a module';

    /**
     * Starter modules that must be installed first, in this order: Permission's
     * migration adds `permissions.display_name`, which every module needs when
     * it registers its own permissions, so Permission (and Role, which uses the
     * same column) must precede the rest. Alphabetical order put Auth first.
     *
     * @var list<string>
     */
    private const CORE_MODULE_ORDER = ['Permission', 'Role', 'User', 'Auth'];

    public function handle(ModuleManager $moduleManager)
    {
        $module = Str::ucfirst($this->argument('name'));
        if ($module) {
            $moduleManager->install($module, $this->output);
            $this->info("Module {$module} installed successfully");
        } else {
            $modules = $this->sortCoreModulesFirst($moduleManager->getModules());
            foreach ($modules as $mod) {
                $moduleManager->install($mod['name'], $this->output);
                $this->info("Module {$mod['name']} installed successfully");
            }
            return;
        }
    }

    /**
     * @param  list<array<string, mixed>>  $modules
     * @return list<array<string, mixed>>
     */
    private function sortCoreModulesFirst(array $modules): array
    {
        $order = array_map('strtolower', self::CORE_MODULE_ORDER);

        usort($modules, function (array $a, array $b) use ($order): int {
            $rankA = array_search(strtolower((string) $a['name']), $order, true);
            $rankB = array_search(strtolower((string) $b['name']), $order, true);

            return ($rankA === false ? PHP_INT_MAX : $rankA) <=> ($rankB === false ? PHP_INT_MAX : $rankB);
        });

        return $modules;
    }
}
