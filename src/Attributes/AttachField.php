<?php

namespace Nodex\Nexus\Attributes;

use Attribute;

/**
 * Declares an admin-panel form field on a module this class doesn't own,
 * without that module's file ever referencing the declaring module.
 * Declarative sugar over #[TargetModule]'s handle(object $configuration)
 * for the common case of adding one field — handle() remains the escape
 * hatch for anything this attribute can't express (conditional visibility,
 * computed defaults, ...).
 *
 * Place on a public method of a class that also carries #[TargetModule] —
 * the method itself is never called, it only carries this attribute (and,
 * for a relation-backed field, a #[Relation] attribute alongside it, the
 * same one a real model's own relation method would carry):
 *
 * #[TargetModule('Product')]
 * class ReviewAdminPlugin
 * {
 *     #[AttachField(name: 'reviews', type: 'relationManager', section: 'relations', label: 'Reviews')]
 *     #[Relation(type: 'hasMany', show: 'title', relatedModule: 'review')]
 *     public function reviewsField(): void {}
 * }
 *
 * The paired #[AttachRelation] (see Attributes/AttachRelation.php, usually
 * declared in the same module's Relations/ folder) is what makes the
 * underlying Eloquent relation actually resolve — this attribute only
 * makes it visible in Product's admin form. Discovered and applied by
 * PluginManager alongside #[TargetModule]/#[Filter]/#[Action] — disabling
 * or removing the declaring plugin/module makes the field disappear on the
 * next request, the same fail-quiet posture as every other Nexus extension
 * point (nothing crashes, it's simply not there).
 */
#[Attribute(Attribute::TARGET_METHOD)]
class AttachField
{
    public function __construct(
        /** Field name, e.g. 'reviews' — must match the paired relation name when relation-backed. */
        public readonly string $name,

        /** Field type, e.g. 'relationManager', 'string', 'view'. */
        public readonly string $type,

        /** Admin form section to render the field in. Falls back to a 'default' section like any other field. */
        public readonly string $section = 'default',

        public readonly ?string $label = null,

        public readonly bool $isRequired = false,

        /** Render order relative to the target module's own fields. */
        public readonly int $order = 0,
    ) {}
}
