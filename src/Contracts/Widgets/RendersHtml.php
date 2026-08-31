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
     * @return array<string, string> Blade view identifier => display name.
     */
    public function availableViews(): array;

    public function viewFor(?string $name): ?string;

    public function render(array $config, WidgetContext $context): string;
}
