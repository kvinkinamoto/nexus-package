<?php

namespace Nodex\Nexus\Attributes;

use Attribute;

/**
 * Declares one column of a 'repeater' field (see field_types/repeater.blade.php).
 * Stack multiple instances on the same relation method — one per column, in
 * declaration order. Separate from #[Field] because #[Field] isn't repeatable
 * and a nested-array parameter on it would be unreadable.
 *
 * The relation method itself still needs #[Field(type: 'repeater', ...)] +
 * #[Relation(type: 'hasMany', ...)] exactly like any other HasMany relation —
 * #[RepeaterField] only adds column metadata on top.
 *
 * Example:
 *   #[Field(type: 'repeater', section: 'products_section', label: 'products')]
 *   #[Relation(type: 'hasMany', ajax: true)]
 *   #[RepeaterField(name: 'product_id', type: 'relation', label: 'product', required: true)]
 *   #[RepeaterField(name: 'quantity', type: 'number', label: 'quantity', required: true, width: '120px')]
 *   public function products(): HasMany { ... }
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class RepeaterField
{
    public function __construct(
        /** Column key — becomes relation[{relationName}][{index}][{name}] on submit. */
        public readonly string $name,

        /** Cell field type, resolved the same way as any #[Field] type (module override -> registry -> built-in). */
        public readonly string $type,

        /** Column header label. Auto-generated from $name if null. */
        public readonly ?string $label = null,

        public readonly bool $required = false,

        /**
         * Validation rules for this column. Not yet applied automatically —
         * collected here for Phase 4.4's NexusRuleCollector to consume.
         */
        public readonly string|array $rules = [],

        /** CSS width for the <th>/<td>, e.g. '120px' or '20%'. */
        public readonly ?string $width = null,

        /**
         * Not yet functionally wired — see field_types/repeater.blade.php's
         * docblock. Stored for a future per-row conditional-visibility pass.
         */
        public readonly array $showWhen = [],

        public readonly string $showWhenLogic = 'and',
    ) {}
}
