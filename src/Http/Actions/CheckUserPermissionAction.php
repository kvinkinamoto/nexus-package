<?php

namespace Nodex\Nexus\Http\Actions;

use Nodex\Nexus\Dto\ModuleDtos\DefaultModuleConfigurationDto;
use Nodex\Nexus\Enums\AdminPanelPermissionEnum;
use Nodex\Nexus\Enums\PermissionPlacesEnum;
use Nodex\Nexus\Events\PermissionChecking;
use Nodex\Nexus\Models\Module;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

class CheckUserPermissionAction
{
    /**
     * spatie/laravel-permission's hasPermissionTo() throws PermissionDoesNotExist
     * rather than returning false when the named permission was never seeded.
     * A module's declared permission name (e.g. from #[Module(permissions:)])
     * isn't guaranteed to exist in every environment/test — GlobalSearchService
     * discovered this by iterating every enabled module's 'index' permission
     * in one request, where previously every caller only ever checked a
     * single already-seeded permission. A permission check must degrade to
     * "denied", not a 500, when the permission itself doesn't exist yet.
     */
    private static function hasPermission($user, string $permission): bool
    {
        try {
            return $user->hasPermissionTo($permission);
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public static function handle(string $action, ?Module $module, string $place = PermissionPlacesEnum::ADMIN_PANEL->value): bool
    {
        // Lets a plugin override or extend a permission decision (e.g. grant
        // a custom role blanket access, or deny an action this method would
        // otherwise allow) without needing to seed/manage a real spatie
        // permission for every such rule.
        $result = self::resolve($action, $module, $place);
        event(new PermissionChecking($action, $module, $place, $result));

        return (bool) nexus_filter('nexus.permission.check', $result, $action, $module, $place);
    }

    private static function resolve(string $action, ?Module $module, string $place): bool
    {
        $user = auth()->user();
        if ($user && self::hasPermission($user, AdminPanelPermissionEnum::ALL->value)) {
            return true;
        }

        $modulePermissions = DefaultModuleConfigurationDto::normalize($module?->config?->permissions ?? []);

        if (!isset($modulePermissions[$place][$action])) {
            return false;
        }

        foreach ($modulePermissions[$place] as $key => $permission) {
            if ($key === $action) {
                if ($user && $permission === '') {
                    return true;
                }
                if ($user && self::hasPermission($user, $permission)) {
                    return true;
                }
                break;
            }
        }

        return false;
    }
}
