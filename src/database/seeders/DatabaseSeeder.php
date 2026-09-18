<?php

namespace Nodex\Nexus\database\seeders;

use Illuminate\Database\Seeder;
use Nodex\Nexus\Services\ModuleManager;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run()
    {
        $senders = ModuleManager::getModuleSeeders();
        $this->call([
            ...$senders,
        ]);
    }
}
