<?php

namespace Nodex\Nexus\Services\Blocks;

/**
 * Central store of every available block type, keyed by BlockTypeDefinition::key().
 * Modeled on Services/Widgets/WidgetRegistry.php's shape (register/find/all),
 * but without that registry's attribute+ModuleManifestCache filesystem
 * discovery — a block type is a small field-set descriptor, not a full
 * #[Widget]/#[Module] class, so a plugin/module just calls register()
 * directly from its own service provider's boot(), same as
 * FieldTypeRegistry::registerView().
 */
class BlockTypeRegistry
{
    /** @var array<string, BlockTypeDefinition> */
    private array $types = [];

    public function register(BlockTypeDefinition $type): void
    {
        $this->types[$type->key()] = $type;
    }

    public function find(?string $key): ?BlockTypeDefinition
    {
        return $key !== null ? ($this->types[$key] ?? null) : null;
    }

    /** @return array<string, BlockTypeDefinition> */
    public function all(): array
    {
        return $this->types;
    }
}
