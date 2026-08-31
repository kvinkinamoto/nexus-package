<?php

namespace Nodex\Nexus\Attributes;

use Attribute;

/**
 * Declares a module-level setting (rendered on the module's settings screen,
 * independent of any table/form for a bound model).
 *
 * Example:
 * #[Setting(name: 'sitemap_mode', type: 'select', label: '...', default: 'single', options: [...], required: true)]
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class Setting
{
    public function __construct(
        public readonly string $name,
        public readonly string $type,
        public readonly ?string $label = null,
        public readonly mixed $default = null,
        public readonly bool $required = false,
        public readonly array $options = [],
        public readonly bool $multiple = false,
        public readonly ?string $comment = null,
    ) {}
}
