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
    ) {}
}
