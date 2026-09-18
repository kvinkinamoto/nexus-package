<?php

namespace Nodex\Nexus\Attributes;

use Attribute;

/**
 * Declares a named, pre-configured alternate view of the index table —
 * a Nova-style Lens, minus the custom-query escape hatch: it reuses the
 * same condition array shape UniversalFilterBuilder already consumes for
 * the ad-hoc "Universal Filters" panel, so switching to a lens is just
 * pre-filling that same filtering pipeline instead of a second one.
 *
 * Example:
 * #[TableLens(name: 'published', label: 'lens_published', conditions: [
 *     ['column' => 'is_published', 'operator' => '=', 'value' => 1],
 * ])]
 * #[TableLens(name: 'out_of_stock', label: 'lens_out_of_stock', conditions: [
 *     ['column' => 'stock', 'operator' => '<=', 'value' => 0],
 * ])]
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class TableLens
{
    public function __construct(
        /** Unique key, referenced by the index page's ?lens= query param */
        public readonly string $name,

        /** Translation key under {module}::translate, shown on the tab */
        public readonly string $label,

        /**
         * Condition array in the same shape UniversalFilterBuilder::apply()
         * accepts: ['column' => ..., 'operator' => ..., 'value' => ..., 'logic' => 'AND'|'OR'].
         */
        public readonly array $conditions = [],

        /** Icon string for the tab (Solar Icons / boxicons key from nexus_icon()) */
        public readonly ?string $icon = null,

        /**
         * Column names to show while this lens is active, overriding the
         * table's normal default/user-saved column selection. Null keeps
         * whatever would otherwise be shown.
         */
        public readonly ?array $columns = null,

        /**
         * Default sort column while this lens is active (always overridden
         * by an explicit ?sort= from the user, same precedence as the
         * table's own defaultSortColumn).
         */
        public readonly ?string $sort = null,
    ) {}
}
