<?php

namespace Nodex\Nexus\Dto\ModuleDtos;

/**
 * One column of a 'repeater' field, built from a single #[RepeaterField]
 * attribute instance by AttributeSchemaReader. See Attributes/RepeaterField.php.
 */
class RepeaterFieldConfigDto extends \stdClass
{
    public function __construct(
        public string $name,
        public string $type,
        public ?string $label = null,
        public bool $required = false,
        public array $rules = [],
        public ?string $width = null,
        public array $showWhen = [],
        public string $showWhenLogic = 'and',
    ) {
        $this->label = $label ?? ucfirst($this->name);
    }
}
