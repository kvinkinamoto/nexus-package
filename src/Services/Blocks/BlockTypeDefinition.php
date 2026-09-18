<?php

namespace Nodex\Nexus\Services\Blocks;

/**
 * One selectable entry in a #[Field(type: 'blockEditor')] field's "Add
 * block" list, e.g. Hero/Text/Image/CTA. Only describes the block's own
 * field schema for admin editing — public rendering resolves separately by
 * convention (nexus::public.block_types.{key}, see @nexusBlocks in
 * NexusServiceProvider), same split repeater cells already have between
 * admin editing (repeater_cells/{type}.blade.php) and how the value ends up
 * used elsewhere.
 */
interface BlockTypeDefinition
{
    /** Discriminator stored in PageBlock::$type — also the key BlockTypeRegistry stores this under. */
    public function key(): string;

    public function label(): string;

    public function icon(): ?string;

    /** @return BlockFieldDefinition[] */
    public function fields(): array;
}
