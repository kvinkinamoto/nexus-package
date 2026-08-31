<?php

namespace Nodex\Nexus\Attributes;

use Attribute;

/**
 * Declares a class as a Nexus module.
 * Place this attribute on an Eloquent Model class.
 *
 * Example:
 * #[Module(name: 'article', label: 'Статті', icon: 'solar:document-bold', group: 'Content')]
 * class Article extends Model { ... }
 */
#[Attribute(Attribute::TARGET_CLASS)]
class Module
{
    public function __construct(
        /** Unique camelCase module name, e.g. 'article', 'shopProduct' */
        public readonly string $name,

        /** Human-readable label shown in the admin panel menu */
        public readonly string $label = '',

        /** Icon string (Solar Icons or FontAwesome), e.g. 'solar:document-bold' */
        public readonly string $icon = 'solar:box-bold',

        /** Parent menu group, e.g. 'Site', 'Shop', 'Users', 'Content' */
        public readonly string $group = 'Site',

        /** Whether to show the module in the sidebar menu */
        public readonly bool $showInMenu = true,

        /** Auto-generate CRUD permissions for this module */
        public readonly bool $permissions = true,

        /** Whether this module represents a tree structure */
        public readonly bool $isTree = false,

        /** Custom menu resolver class */
        public readonly ?string $menuResolver = null,

        /**
         * Explicit model class this configuration binds to, for when #[Module]
         * is placed on a class other than the Eloquent model itself (e.g. a
         * dedicated ModuleConfiguration class pointing at a vendor-package
         * model, or a model shared with another module). Defaults to the
         * annotated class itself when null and that class is an Eloquent model.
         */
        public readonly ?string $model = null,

        /**
         * Names of other modules this one needs to actually function
         * (e.g. Wishlist requires ShopProduct). Purely declarative — does
         * NOT block install or enable. A module with unmet requires still
         * installs and enables; ModuleDependencyChecker/the admin panel
         * banner surface the gap instead of refusing it, since the fix
         * might just be "install the other module", not "reject this one".
         */
        public readonly array $requires = [],

        /**
         * Render the create/edit form as a multi-step wizard instead of a
         * single page. Requires at least one #[Section(tab:)] group — the
         * existing tabs become the wizard's steps, navigated with Next/Back
         * instead of freely clickable tabs.
         */
        public readonly bool $wizard = false,

        /**
         * Open the edit form in an offcanvas panel from the index list
         * instead of navigating to a full edit page. Nodex\Nexus\Livewire\ModuleTable
         * mounts ModuleForm directly inside the panel (see
         * livewire/module-table.blade.php) — no iframe, no separate HTTP
         * round trip for the panel's own content.
         */
        public readonly bool $slideOver = false,

        /**
         * Historical opt-in flag from the module-by-module Livewire rollout
         * (Фаза 8.2). Every real admin module is on Livewire now and
         * NexusController's index/edit/create actions no longer branch on
         * this value — it's inert metadata kept for backward compatibility
         * with existing #[Module(...)] declarations rather than something
         * still read at runtime.
         */
        public readonly bool $livewire = false,
    ) {}
}
