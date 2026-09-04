<?php

namespace Nodex\Nexus\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Laravel-native counterpart to the nexus.export.row plugin filter, fired
 * right before it in MasterExportJob for every exported row — a module's
 * own Listeners/ folder can reformat or redact a value without a plugin
 * class. $row is the ordered array of CSV cell strings, by reference.
 *
 *   class RedactDemoSecretOnExport {
 *       public function handle(ExportRowBuilding $event): void {
 *           if ($event->moduleName !== 'demo') return;
 *           // ...mutate $event->row here...
 *       }
 *   }
 */
class ExportRowBuilding
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Model $model,
        public array &$row,
        public string $moduleName
    ) {
    }
}
