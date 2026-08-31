<?php

namespace Nodex\Nexus\Dto\Widgets;

/**
 * Normalized shape for a single-number metric widget (dashboard card,
 * sparkline). Returned by ProvidesMetric::toMetric() so the admin dashboard
 * renderer can lay out any metric widget the same way without knowing its
 * internals. extends \stdClass to match FieldConfigDto's convention
 * elsewhere in this package.
 */
class MetricDto extends \stdClass
{
    public function __construct(
        public string $label,
        public int|float|string $value,
        public int|float|string|null $previous = null,
        public ?float $deltaPercent = null,
        /** 'up' | 'down' | 'flat' | null */
        public ?string $direction = null,
        /** Sparkline points, oldest first. */
        public array $series = [],
        public ?string $unit = null,
        public ?string $icon = null,
        public ?string $link = null,
    ) {}
}
