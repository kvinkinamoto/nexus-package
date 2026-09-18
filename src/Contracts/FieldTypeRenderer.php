<?php

namespace Nodex\Nexus\Contracts;

use Illuminate\Contracts\View\View;
use Nodex\Nexus\Dto\FieldRenderContext;

/**
 * Implemented by a class registered via FieldTypeRegistry::registerClass().
 * Resolved through the container, so constructor dependencies are injected
 * normally.
 *
 * A module's FieldTypes/ folder is auto-discovered too (see
 * ModuleManifestCache::discoverFieldTypes()) — no module in this codebase
 * has one yet, so there's no concrete implementer to point to as an
 * example. The discovery pipeline is real and wired up regardless.
 */
interface FieldTypeRenderer
{
    public function render(FieldRenderContext $context): View|string;
}
