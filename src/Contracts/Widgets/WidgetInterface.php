<?php

namespace Nodex\Nexus\Contracts\Widgets;

use Nodex\Nexus\Dto\Widgets\WidgetContext;

/**
 * The only contract every widget must implement. Data acquisition is
 * separated from presentation on purpose: getData() is the single
 * serialization point, so the API surface (Phase 5.5) is just "call
 * getData() and json-encode it" — no separate data layer per surface.
 *
 * Metadata (name, label, surfaces, icon, ...) lives on #[Widget] instead of
 * in this interface — see Attributes/Widget.php. WidgetRegistry cross-checks
 * the attribute's declared surfaces against which optional capability
 * interfaces (RendersHtml, ProvidesMetric, ApiSerializable) a widget actually
 * implements, and logs a mismatch rather than failing silently.
 */
interface WidgetInterface
{
    /**
     * @return array<string, mixed>
     */
    public function getData(array $config, WidgetContext $context): array;

    /**
     * Config fields shown when an admin places/configures this widget.
     * Same FieldConfigDto shape used by module forms — see Dto/ModuleDtos/FieldConfigDto.
     *
     * @return array<int, object>
     */
    public static function configFields(): array;
}
