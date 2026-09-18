<?php

namespace Nodex\Nexus\commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class Install extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nexus:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initialize Nexus package';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(

    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): string
    {
        $this->createNecessaryDirectory();

        return 'output';
    }

    private function createNecessaryDirectory(): void
    {
        $dirs = [
            app_path('Nexus/Modules'),
            app_path('Nexus/Plugins'),
        ];

        foreach ($dirs as $dir) {
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
                $this->info("📁 Створено: {$dir}");
            } else {
                $this->info("✅ Вже існує: {$dir}");
            }
        }

        Artisan::call('nexus:resource:publish', [], $this->getOutput());

        // The admin layout's header unconditionally queries unreadNotifications()
        // (see resources/views/nexus/layouts/header.blade.php) — that's Laravel's
        // own notifications table, not something nexus-migrations creates.
        // migrationExists() inside notifications:table already no-ops if the
        // migration is already there, so this is safe to run on every install.
        Artisan::call('notifications:table', [], $this->getOutput());
        Artisan::call('migrate', [], $this->getOutput());

        Artisan::call('nexus:permission:init', [], $this->getOutput());

        Artisan::call('nexus:module:install', [], $this->getOutput());

        //        Artisan::call('nexus:default_module:publish', [], $this->getOutput());

        $this->info('✔️ Встановлення Nexus завершено!');

    }
}
