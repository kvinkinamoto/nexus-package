<?php

namespace Nodex\Nexus\Contracts\Widgets;

use Nodex\Nexus\Dto\Widgets\MetricDto;
use Nodex\Nexus\Dto\Widgets\WidgetContext;

/**
 * Optional capability for widgets that boil down to a single tracked number
 * (revenue, orders count, ...). Lets AbstractMetricWidget / the dashboard's
 * sparkline+badge layout (Phase 5.4) render any such widget uniformly
 * without inspecting its raw getData() shape.
 */
interface ProvidesMetric
{
    public function toMetric(array $config, WidgetContext $context): MetricDto;
}
