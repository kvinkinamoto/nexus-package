<?php

namespace Nodex\Nexus\Attributes;

use Attribute;

/**
 * Declares an Eloquent global scope on a model this class doesn't own,
 * without that model's file referencing the declaring module. Backed by
 * Laravel's own Model::addGlobalScope() — standard Eloquent, not a
 * Nexus-specific mechanism.
 *
 * Place on a public static method under a module's Relations/ folder.
 * The method takes no arguments and must return a Closure(Builder $query)
 * or a Scope instance:
 *
 * #[AttachScope(model: \App\Nexus\Modules\ShopProduct\Models\ShopProduct::class, name: 'published')]
 * public static function published(): \Closure
 * {
 *     return fn ($query) => $query->where('is_published', true);
 * }
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class AttachScope
{
    public function __construct(
        /** Fully-qualified class name of the model being extended. */
        public readonly string $model,

        /** Scope identifier used by Model::addGlobalScope(). Defaults to the method name if null. */
        public readonly ?string $name = null,
    ) {}
}
