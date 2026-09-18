<?php

namespace Nodex\Nexus\Exceptions;

/**
 * Thrown when a module's attributes are structurally invalid in a way
 * nothing downstream can recover from — a #[Field(type: 'relation')] with no
 * matching #[Relation], or a #[Requests] class that doesn't exist. Thrown
 * from AttributeSchemaReader::read(), which runs both at module install/
 * manifest-cache time and on every request that renders the module, so a
 * broken module fails loudly wherever it's actually hit instead of quietly
 * producing a broken DTO that only surfaces as a confusing failure several
 * layers downstream.
 */
class ModuleMisconfiguredException extends \RuntimeException
{
}
