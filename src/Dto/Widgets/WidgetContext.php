<?php

namespace Nodex\Nexus\Dto\Widgets;

use Illuminate\Contracts\Auth\Authenticatable;
use Nodex\Nexus\Enums\WidgetSurface;

/**
 * Everything a widget's getData() might need to know about where and how
 * it's being rendered, gathered in one place so new capabilities are added
 * as a field here rather than as a new parameter on every widget's methods.
 */
final class WidgetContext
{
    public function __construct(
        public readonly WidgetSurface $surface,
        public readonly ?string $position = null,
        public readonly ?string $templateType = null,
        public readonly ?int $instanceId = null,
        public readonly ?Authenticatable $user = null,
        public readonly ?string $locale = null,
        public readonly array $params = [],
    ) {}
}
