<?php

namespace Nodex\Nexus\Attributes;

use Attribute;

/**
 * Declares a custom permission for a Nexus module action.
 *
 * This attribute REGISTERS the permission in the system so it appears in the admin panel
 * and can be assigned to roles/users from there.
 * It does NOT assign the permission to any role — that is managed by the admin.
 *
 * Can be placed on the model class (for custom global permissions)
 * or on a method inside a custom controller (for custom action permissions).
 *
 * Example on Model class:
 *   #[Permission(action: 'export', label: 'Export Articles')]
 *   #[Permission(action: 'import', label: 'Import Articles')]
 *   class Article extends Model { ... }
 *
 * Example on controller method:
 *   #[Permission(action: 'export', label: 'Export')]
 *   public function export(Request $request, Module $module): Response { ... }
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Permission
{
    public function __construct(
        /**
         * The action key, used as the permission name suffix.
         * Full permission name will be: {moduleName}_{action}
         * Example: article_export
         */
        public readonly string $action,

        /**
         * Human-readable label shown in the admin panel permission manager.
         * Auto-generated from action if null.
         */
        public readonly ?string $label = null,

        /**
         * Which guard to register this permission under.
         */
        public readonly string $guard = 'web',
    ) {}
}
