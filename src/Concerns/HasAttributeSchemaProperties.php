<?php

namespace Nodex\Nexus\Concerns;

use Nodex\Nexus\Attributes\Column;
use Nodex\Nexus\Attributes\Field;
use ReflectionClass;
use ReflectionProperty;

/**
 * #[Field]/#[Column] can only be attached to a real declared PHP property —
 * AttributeSchemaReader reflects the class, and PHP attributes can't be
 * attached to Eloquent's virtual/magic attributes. But a declared property
 * permanently shadows Eloquent's __get()/__set() for that name: reading it
 * silently returns null instead of the real DB value, and direct writes to
 * it never reach $attributes, so they're silently lost on save().
 *
 * `use` this trait on every model that carries #[Field]/#[Column] on
 * properties (not methods). Hooks into Eloquent's own
 * initialize{TraitName}() convention (see Model::bootTraits()) to unset
 * those declared properties right after construction — for every
 * instantiation path, hydrated or fresh — restoring normal Eloquent
 * attribute access under their real names.
 */
trait HasAttributeSchemaProperties
{
    /** @var array<class-string, string[]> */
    private static array $attributeSchemaPropertyNames = [];

    public function initializeHasAttributeSchemaProperties(): void
    {
        $class = static::class;

        if (!isset(self::$attributeSchemaPropertyNames[$class])) {
            self::$attributeSchemaPropertyNames[$class] = $this->discoverAttributeSchemaPropertyNames();
        }

        foreach (self::$attributeSchemaPropertyNames[$class] as $name) {
            unset($this->$name);
        }
    }

    /**
     * @return string[]
     */
    private function discoverAttributeSchemaPropertyNames(): array
    {
        $reflection = new ReflectionClass(static::class);
        $names = [];

        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC | ReflectionProperty::IS_PROTECTED | ReflectionProperty::IS_PRIVATE) as $property) {
            if ($property->isStatic()) {
                continue;
            }

            if ($property->getAttributes(Field::class) || $property->getAttributes(Column::class)) {
                $names[] = $property->getName();
            }
        }

        return $names;
    }
}
