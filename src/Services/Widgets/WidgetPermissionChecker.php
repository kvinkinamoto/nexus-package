<?php

namespace Nodex\Nexus\Services\Widgets;

use Illuminate\Contracts\Auth\Authenticatable;
use Nodex\Nexus\Attributes\Widget;
use Nodex\Nexus\Enums\AdminPanelPermissionEnum;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

/**
 * Backs #[Widget(permission:)]. Mirrors the ALL-permission bypass already
 * used by Http\Actions\CheckUserPermissionAction for module actions, so a
 * super-admin isn't blocked from a widget by an unrelated missing grant.
 *
 * spatie/laravel-permission's hasPermissionTo() throws PermissionDoesNotExist
 * rather than returning false when the named permission was never seeded
 * (e.g. nexus:permission:init hasn't run yet, or a widget declares a
 * permission nobody created) — a widget check must degrade to "denied", not
 * a 500, in that case.
 */
class WidgetPermissionChecker
{
    public static function check(Widget $meta, ?Authenticatable $user): bool
    {
        if (!$meta->permission) {
            return true;
        }

        if (!$user || !method_exists($user, 'hasPermissionTo')) {
            return false;
        }

        try {
            if ($user->hasPermissionTo(AdminPanelPermissionEnum::ALL->value)) {
                return true;
            }

            return $user->hasPermissionTo($meta->permission);
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }
}
