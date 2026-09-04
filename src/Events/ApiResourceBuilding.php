<?php

namespace Nodex\Nexus\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Laravel-native counterpart to the nexus.api.resource plugin filter, fired
 * right before it in NexusResource::toArray() — a module's own Listeners/
 * folder can add/hide/reshape a field in its own generic API output without
 * a plugin class. $data is by reference. Only reached by the generic
 * fallback resource — a module with its own {Model}Resource class (see
 * NexusResource::resolveFor()) never hits this at all.
 *
 *   class HideDemoSecret {
 *       public function handle(ApiResourceBuilding $event): void {
 *           if (!$event->resource instanceof \App\Nexus\Modules\Demo\Models\Demo) return;
 *           unset($event->data['secret']);
 *       }
 *   }
 */
class ApiResourceBuilding
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Model $resource,
        public array &$data
    ) {
    }
}
