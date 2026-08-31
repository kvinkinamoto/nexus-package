<?php

namespace Nodex\Nexus\Attributes;

use Attribute;

/**
 * Marks a model property as a column in the Nexus admin list table.
 * Can be combined with #[Field] on the same property.
 *
 * Example:
 * #[Column(label: 'Назва', sortable: true)]
 * #[Field(type: 'string', section: 'main', translated: true)]
 * public string $title;
 *
 * Interactive toggle example:
 * #[Column(label: 'Активний', action: 'boolToggle', fieldName: 'is_active')]
 * #[Field(type: 'boolean', section: 'settings')]
 * public bool $is_active;
 *
 * Custom Blade component example:
 * #[Column(label: 'Фото', customField: 'showImage')]
 * public string $image;
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD)]
class Column
{
    public function __construct(
        /** Column header label */
        public readonly ?string $label = null,

        /** Whether this column is sortable */
        public readonly bool $sortable = false,

        /**
         * Special interactive action for this column.
         * Built-in values: 'boolToggle', 'ordering'
         */
        public readonly ?string $action = null,

        /**
         * DB field name if it differs from the property name.
         * Used by actions like 'boolToggle' to know which column to update.
         */
        public readonly ?string $fieldName = null,

        /**
         * Name of a custom Blade partial in
         * 'templates/custom_index_fields/' to render this cell.
         * Example: 'showImage'
         */
        public readonly ?string $customField = null,

        /** Show confirmation dialog before executing the action */
        public readonly bool $actionConfirm = false,

        /** Whether this column is visible in the table by default */
        public readonly bool $tableDefault = true,

        /**
         * Display order in the table (lower = further left).
         * If not specified, order follows declaration order in the class.
         */
        public readonly int $order = 0,

        /**
         * Opt this column into GlobalSearchService's cross-module search.
         * Deliberately explicit — unlike the per-module search filter
         * (Filters/FilterHandler.php), which blindly LIKEs every physical
         * column, a global search across every enabled module needs an
         * opt-in so it doesn't leak internal/irrelevant columns.
         */
        public readonly bool $searchable = false,
    ) {}
}
