<?php

namespace Nodex\Nexus\Services;


use Nodex\Nexus\Enums\AdminPanelPermissionEnum;
use Nodex\Nexus\Events\SidebarMenuBuilding;

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

        // Lets a plugin append an entirely new top-level menu entry (not
        // tied to any single module's own #[Module] config) or re-order/
        // drop what's already here.
        event(new SidebarMenuBuilding($menu));

        return nexus_filter('nexus.menu.sidebar', $menu);
    }
}
