<?php

namespace Nodex\Nexus\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

/**
 * Laravel-native counterpart to the nexus.module.discovery plugin filter,
 * fired right before it in ModuleRegistry::getAllModules() — after the
 * normal filesystem scan of both module roots. Lets a module register
 * itself (or hide another) without existing as a real directory under
 * Modules/ or UserModules/ — e.g. a package shipping a module purely in
 * PHP. $modules is by reference, keyed by lowercase name, same shape
 * scanDirectory() itself produces (name/path/namespace/is_user_module).
 *
 *   class RegisterPackagedReportsModule {
 *       public function handle(ModuleDiscoveryCompleted $event): void {
 *           $event->modules->put('reports', [
 *               'name' => 'Reports', 'path' => __DIR__,
 *               'namespace' => 'Acme\\Reports', 'is_user_module' => false,
 *           ]);
 *       }
 *   }
 */
class ModuleDiscoveryCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Collection &$modules
    ) {
    }
}
