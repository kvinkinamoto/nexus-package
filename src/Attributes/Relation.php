<?php

namespace Nodex\Nexus\Attributes;

use Attribute;

/**
 * Configures how an Eloquent relation method is exposed in the admin panel.
 * Place this above the relation method alongside #[Field].
 *
 * Example (BelongsToMany, small table — preloaded like a select):
 * #[Field(type: 'relation', section: 'roles_and_permissions')]
 * #[Relation(type: 'belongsToMany', show: 'name')]
 * public function roles(): BelongsToMany { ... }
 *
 * Example (BelongsTo with ajax search, large table):
 * #[Field(type: 'relation', section: 'settings')]
 * #[Relation(type: 'belongsTo', show: 'name', ajax: true, ajaxMode: 'search')]
 * public function category(): BelongsTo { ... }
 *
 * Example (HasMany / gallery):
 * #[Field(type: 'images', section: 'media')]
 * #[Relation(type: 'hasMany')]
 * public function images(): HasMany { ... }
 *
 * Example (BelongsToMany):
 * #[Field(type: 'relation', section: 'settings')]
 * #[Relation(type: 'belongsToMany', show: 'name', ajax: true, ajaxMode: 'load')]
 * public function tags(): BelongsToMany { ... }
 *
 * Can also target a property of the same name as the relation method, for a
 * relation that comes from a trait the model doesn't redeclare (e.g.
 * Spatie\Permission's HasRoles::roles()) — there's no method on the model
 * itself to attach the attribute to. type: must be explicit in that case;
 * see AttributeSchemaReader::processRelationAttr().
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_PROPERTY)]
class Relation
{
    public function __construct(
        /**
         * Relation type. Values: 'belongsTo', 'hasMany', 'belongsToMany', 'hasOne'.
         * If omitted, Nexus will try to auto-detect from the Eloquent return type.
         */
        public readonly ?string $type = null,

        /** Field on the related model to show in selects and labels (e.g. 'name', 'title') */
        public readonly string $show = 'name',

        /**
         * Field to fall back to (in both label display and search) when
         * `show`'s value is empty for a given record — e.g. an optional
         * translatable 'display_name' with a mandatory 'name' as fallback.
         */
        public readonly ?string $showFallback = null,

        /** Whether selecting a value is required */
        public readonly bool $required = false,

        /**
         * Opt into search-only ajax loading (recommended for large datasets).
         * false (default): the option list is preloaded (up to 50) on page
         * load, so the field behaves like a plain select — fine for small
         * tables (roles, categories, attributes...).
         * true: nothing is preloaded; results only load once the user
         * types, unless ajaxMode is 'load' (see below).
         */
        public readonly bool $ajax = false,

        /**
         * Only relevant when ajax: true.
         * 'search' — loads results filtered by a search query only (for large tables).
         * 'load'   — preloads all items upfront AND still supports search (for medium tables).
         */
        public readonly string $ajaxMode = 'search',

        /**
         * A custom API Resource class for transforming AJAX response items.
         * Example: \App\Http\Resources\CategoryAjaxResource::class
         */
        public readonly ?string $ajaxResource = null,

        /**
         * The related model's OWN nexus module name — required for
         * #[Field(type: 'relationManager')]. Lets the read-only relation
         * manager link each row to that module's own edit/delete actions
         * and build a "view all" link filtered to the parent record, instead
         * of re-implementing that module's CRUD inline.
         */
        public readonly ?string $relatedModule = null,
    ) {}
}
