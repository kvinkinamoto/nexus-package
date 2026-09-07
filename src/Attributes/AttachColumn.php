<?php

namespace Nodex\Nexus\Attributes;

use Attribute;

/**
 * Declares an admin-panel table column on a module this class doesn't own,
 * without that module's file ever referencing the declaring module.
 * Declarative sugar over #[TargetModule]'s handle(object $configuration)
 * for the common case of adding one column — see Attributes/AttachField.php
 * for the full pattern this mirrors, and Attributes/AttachRelation.php for
 * the data-layer half of the same "specialized module extends a base
 * module" idea.
 *
 * Place on a public method of a class that also carries #[TargetModule] —
 * the method itself is never called, it only carries this attribute:
 *
 * #[TargetModule('Product')]
 * class ReviewAdminPlugin
 * {
 *     #[AttachColumn(name: 'reviews_count', label: 'Reviews')]
 *     public function reviewsCountColumn(): void {}
 * }
 *
 * The column's value still has to resolve on the target model like any
 * other column (a real attribute or accessor) — this attribute only makes
 * an entry appear in the table config, it doesn't manufacture data.
 */
#[Attribute(Attribute::TARGET_METHOD)]
class AttachColumn
{
    public function __construct(
        public readonly string $name,

        public readonly ?string $label = null,

        public readonly bool $sortable = false,

        /** Whether this column is visible by default before any per-user column-visibility preference applies. */
        public readonly bool $tableDefault = true,

        /** Render order relative to the target module's own columns. */
        public readonly int $order = 0,
    ) {}
}
