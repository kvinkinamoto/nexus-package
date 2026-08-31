<?php

namespace Nodex\Nexus\Attributes;

use Attribute;

/**
 * Overrides the CSS class (width) of a form layout column.
 * Place on the Model/ModuleConfiguration class (multiple allowed).
 *
 * Example:
 * #[SectionColumn(name: 'left', class: 'col-lg-8')]
 * #[SectionColumn(name: 'right', class: 'col-lg-4')]
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class SectionColumn
{
    public function __construct(
        public readonly string $name,
        public readonly string $class,
        public readonly ?string $tab = null,
    ) {}
}
