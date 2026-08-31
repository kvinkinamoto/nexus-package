<?php

namespace Nodex\Nexus\Contracts\Widgets;

use Nodex\Nexus\Dto\Widgets\WidgetContext;

/**
 * Optional capability for widgets whose API payload must differ from their
 * getData() result (e.g. hiding an internal field, reshaping for a public
 * contract). When absent, the API surface falls back to getData() as-is.
 */
interface ApiSerializable
{
    /**
     * @return array<string, mixed>
     */
    public function toApiPayload(array $config, WidgetContext $context): array;
}
