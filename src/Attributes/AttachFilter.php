<?php

namespace Nodex\Nexus\Attributes;

use Attribute;

/**
 * Declares an admin-panel table filter on a module this class doesn't own,
 * without that module's file ever referencing the declaring module. See
 * Attributes/AttachField.php for the full pattern this mirrors.
 *
 * Place on a public method of a class that also carries #[TargetModule] —
 * the method itself is never called, it only carries this attribute:
 *
 * #[TargetModule('Product')]
 * class ReviewAdminPlugin
 * {
 *     #[AttachFilter(name: 'has_reviews', label: 'Has reviews', type: 'search')]
 *     public function hasReviewsFilter(): void {}
 * }
 *
 * A `type: 'search'` (the default, matching #[TableFilter]'s own default)
 * needs no further wiring — Services/Filters/FilterHandler::filter()'s
 * generic 'search' case already LIKE-matches across every column of the
 * target table, module-agnostic, the same way it does for a #[TableFilter]
 * declared directly on the model (see
 * Services/Actions/Admin/AddFilterActionMethod.php's fallback to the base
 * FilterHandler). A custom filter `type` still needs the target module's
 * own FilterHandler to actually implement that case — this attribute only
 * makes the filter box/option appear, same "doesn't manufacture data"
 * caveat as Attributes/AttachColumn.php.
 *
 * `permission`, when set, gates the filter per-viewer — see
 * Attributes/AttachField.php's docblock for the full rationale and gating
 * mechanics this mirrors.
 */
#[Attribute(Attribute::TARGET_METHOD)]
class AttachFilter
{
    public function __construct(
        public readonly string $name,

        public readonly ?string $label = null,

        public readonly string $type = 'search',

        /** Spatie permission name required to see this filter at all. Null = inherit the target module's own gate. */
        public readonly ?string $permission = null,
    ) {}
}
