<?php

namespace Nodex\Nexus\Attributes;

use Attribute;

/**
 * Declares a named form section and its position in the layout grid.
 * Place this attribute on the Model class (multiple allowed).
 *
 * Example (standard two-column layout):
 * #[Section(name: 'main',     column: 'left',  type: 'columns_2', icon: 'solar:document-bold')]
 * #[Section(name: 'settings', column: 'right', type: 'base',      icon: 'solar:settings-bold')]
 * #[Section(name: 'media',    column: 'left',  type: 'base',      icon: 'solar:camera-bold')]
 * #[Section(name: 'seo',      column: 'right', type: 'base',      icon: 'solar:magnifer-bold')]
 *
 * Example with a tab:
 * #[Section(name: 'attributes', column: 'attrs_col', type: 'base', tab: 'attributes')]
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class Section
{
    public function __construct(
        /** Unique section key referenced by #[Field(section: '...')] */
        public readonly string $name,

        /**
         * The layout column this section belongs to.
         * Must match a SectionColumn name ('left', 'right', or custom).
         * If omitted, the section is placed without a column constraint.
         */
        public readonly string $column = 'right',

        /**
         * Section rendering type (maps to a Blade partial in templates/sections/).
         * Built-in values: 'base', 'columns_2', 'information'
         */
        public readonly string $type = 'base',

        /**
         * Icon for the section card header (Solar Icons string).
         * Example: 'solar:document-bold', 'solar:settings-bold'
         */
        public readonly string $icon = 'solar:box-bold',

        /**
         * Tab key to assign this section's column to.
         * Must match a tab name declared via #[Tab] on the same model.
         */
        public readonly ?string $tab = null,
    ) {}
}
