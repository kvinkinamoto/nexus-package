<?php

namespace Nodex\Nexus\Services;


use Nodex\Nexus\Enums\AdminPanelPermissionEnum;

class ModuleServiceForAdminPanel
{

    public function __construct(private ModuleManager $moduleManager)
    {

    }

    public function getSideBarMenu()
    {
        $menu = [];

        $modules = $this->moduleManager->getEnabledModules();
        foreach ($modules as $name => $module) {

            $isPermit = ModuleManager::checkPermission(AdminPanelPermissionEnum::SHOW_SIDE_MENU->value, $module);

            if (!$isPermit) {
                continue;
            }

            $config = $module->config;
            if (!isset($config) || empty($config)) {
                continue;
            }

            if (!$config->menu->show) {
                continue;
            }

            $item = $config->menu;
            $item->module = $module->name;

            $menu[] = $item;
        }
        return $menu;
    }
}
