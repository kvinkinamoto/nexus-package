<?php

namespace Nodex\Nexus\Attributes;

use Attribute;

/**
 * Declares an Eloquent relation on a model this class doesn't own, without
 * that model's file ever referencing the declaring module. Backed by
 * Laravel's own Model::resolveRelationUsing() — this is standard Eloquent,
 * not a Nexus-specific mechanism.
 *
 * Place on a public static method under a module's Relations/ folder
 * (e.g. app/Nexus/Modules/ProductReview/Relations/ShopProductRelations.php).
 * The method receives the target model instance and must return a Relation:
 *
 * #[AttachRelation(model: \App\Nexus\Modules\ShopProduct\Models\ShopProduct::class, name: 'reviews')]
 * public static function reviews(ShopProduct $product): HasMany
 * {
 *     return $product->hasMany(Review::class, 'product_id');
 * }
 *
 * This inverts the usual direction of coupling: the specialized module
 * (ProductReview) knows about the base module (ShopProduct) it attaches to,
 * not the other way around — ShopProduct never has to know ProductReview
 * exists.
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class AttachRelation
{
    public function __construct(
        /** Fully-qualified class name of the model being extended. */
        public readonly string $model,

        /** Relation name, e.g. 'reviews' — accessed as $product->reviews. */
        public readonly string $name,
    ) {}
}
