<?php

namespace Nodex\Nexus\Contracts\Widgets;

use Nodex\Nexus\Dto\Widgets\WidgetContext;

/**
 * Optional capability for widgets that render Blade output (front/admin
 * surfaces). An API-only widget (surfaces: [Api]) has no reason to implement
 * this — WidgetRenderer/AdminDashboardRenderer check for it before calling.
 */
interface RendersHtml
{
    /**
     * Views live next to the widget class itself — e.g.
     * app/Nexus/Widgets/{Name}/{lowerName}.blade.php — not under
     * resources/views/, so a widget stays self-contained in its own folder.
     * render() below is expected to resolve the returned identifier as a
     * filename relative to the implementing class's own directory (typically
     * `__DIR__ . '/' . $view`) and hand it to View::file(), rather than
     * looking it up as a resources/views dot-notation name.
     *
     * @return array<string, string> Blade file name => display name.
     */
    public function availableViews(): array;

    public function viewFor(?string $name): ?string;

    public function render(array $config, WidgetContext $context): string;
}
