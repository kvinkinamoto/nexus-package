<?php

namespace Nodex\Nexus\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class TableFilter
{
    /**
     * @param  string|null  $optionsModel  For type: 'select' — an Eloquent model class whose rows populate the dropdown, queried fresh on every render (e.g. ShopCategory::class). Ignored for every other type.
     * @param  string  $optionsValue  Column used as each <option>'s value (and the value AddFilterActionMethod hands to the module's FilterHandler).
     * @param  string  $optionsLabel  Column shown as each <option>'s label, and the column results are ordered by.
     */
    public function __construct(
        public readonly string $name,
        public readonly string $label,
        public readonly string $type,
        public readonly ?string $optionsModel = null,
        public readonly string $optionsValue = 'id',
        public readonly string $optionsLabel = 'name',
    ) {}
}
