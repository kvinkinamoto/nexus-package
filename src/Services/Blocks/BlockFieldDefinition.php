<?php

namespace Nodex\Nexus\Services\Blocks;

/**
 * One editable field belonging to a single #[block type], e.g. Hero's
 * 'headline'. Deliberately not the #[RepeaterField] attribute — that one is
 * semantically bound to a fixed relation-column context (see its own
 * docblock); a block type's field set varies per row, so it's described by
 * plain DTOs a BlockTypeDefinition returns from fields(), not by attributes
 * reflected off a class.
 */
final class BlockFieldDefinition
{
    public function __construct(
        public readonly string $name,
        public readonly string $type,
        public readonly string $label,
        public readonly bool $required = false,
        /**
         * When true, this field's value in PageBlock::$data is stored as a
         * locale-keyed map (['uk' => '...', 'en' => '...']) instead of a
         * plain scalar — see BlockDataLocalizer (public rendering) and
         * ManagesBlockFields (admin editing). Only 'string'/'text' block
         * cell types render a translatable row per active locale today.
         */
        public readonly bool $translatable = false,
    ) {}
}
