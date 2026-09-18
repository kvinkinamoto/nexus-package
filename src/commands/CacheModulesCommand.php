<?php

namespace Nodex\Nexus\commands;

use Illuminate\Console\Command;
use Nodex\Nexus\Services\ModuleManifestCache;
use Nodex\Nexus\Services\ModuleRegistry;
use Nodex\Nexus\Services\PathManager;

class CacheModulesCommand extends Command
{
    protected $signature = 'nexus:module:cache';
    protected $description = 'Compile the per-module filesystem manifest (views/routes/translations/icons/commands/listeners) into bootstrap/cache/nexus-modules.php so requests skip live scanning';

    public function handle(ModuleManifestCache $cache, ModuleRegistry $registry, PathManager $pathManager): int
    {
        $registry->refresh();

        $manifest = $cache->build($registry->getAllModules(), $pathManager);
        $cache->write($manifest);

        $this->info('Nexus module manifest cached: ' . $cache->path());
        $this->info(count($manifest['modules']) . ' modules compiled.');

        return self::SUCCESS;
    }
}
