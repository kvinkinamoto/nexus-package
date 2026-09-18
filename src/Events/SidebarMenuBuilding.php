<?php

namespace Nodex\Nexus\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Laravel-native counterpart to the nexus.menu.sidebar plugin filter, fired
 * right before it in ModuleServiceForAdminPanel::getSideBarMenu() — a
 * module's own Listeners/ folder can append a top-level menu entry not tied
 * to any single module's own #[Module] config, or re-order/drop what's
 * already there. $menu is by reference, an array of MenuConfigDto.
 *
 *   class AppendReportsMenuEntry {
 *       public function handle(SidebarMenuBuilding $event): void {
 *           $event->menu[] = new \Nodex\Nexus\Dto\ModuleDtos\MenuConfigDto(name: 'reports', label: 'Reports');
 *       }
 *   }
 */
class SidebarMenuBuilding
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public array &$menu
    ) {
    }
}
