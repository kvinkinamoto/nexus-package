<?php

namespace Nodex\Nexus\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Nodex\Nexus\Services\FieldTypeRegistry;

/**
 * Laravel-native counterpart to the nexus.field_types.register plugin
 * action, fired right before it at the same call site — a module's own
 * Listeners/ folder can register/alias a field type without a plugin class.
 *
 *   class RegisterShopFieldTypes {
 *       public function handle(FieldTypesRegistering $event): void {
 *           $event->registry->alias('sku', 'string');
 *       }
 *   }
 */
class FieldTypesRegistering
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public FieldTypeRegistry $registry
    ) {
    }
}
