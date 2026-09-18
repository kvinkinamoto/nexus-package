<?php

namespace Nodex\Nexus\commands;

use Illuminate\Console\Command;
use Nodex\Nexus\Services\ModuleManifestCache;

class ClearModuleCacheCommand extends Command
{
    protected $signature = 'nexus:module:clear';
    protected $description = 'Remove the compiled Nexus module manifest, reverting to live filesystem scanning on every request';

    public function handle(ModuleManifestCache $cache): int
    {
        $cache->clear();

        $this->info('Nexus module manifest cleared.');

        return self::SUCCESS;
    }
}
