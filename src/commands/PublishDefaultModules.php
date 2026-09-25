<?php

namespace Nodex\Nexus\commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PublishDefaultModules extends Command
{
    protected $signature = 'nexus:default_module:publish 
                            {--module= : Module name or comma-separated list}
                            {--force : Overwrite existing modules}';

    protected $description = 'Publish Nexus default modules';

    public function handle(): int
    {
        $this->publishModules();

        $this->info('Publish finished!');

        return self::SUCCESS;
    }

    protected function publishModules(): void
    {
        $sourcePath = $this->getVendorModulesPath();
        $targetPath = app_path('Nexus\\Modules');

        if (! File::exists($sourcePath)) {
            $this->error("Source path not found: {$sourcePath}");

            return;
        }

        File::ensureDirectoryExists($targetPath);

        $requestedModules = $this->getRequestedModules();

        $modules = File::directories($sourcePath);

        foreach ($modules as $modulePath) {
            $moduleName = basename($modulePath);

            if (! empty($requestedModules) && ! in_array($moduleName, $requestedModules)) {
                continue;
            }

            $destination = $targetPath.'\\'.$moduleName;

            if (File::exists($destination)) {
                if (! $this->option('force')) {
                    $this->warn("⚠️ {$moduleName} already exists — skipped");

                    continue;
                }

                // copyDirectory() only overlays — it never removes files already
                // present at the destination that aren't in the source. Without
                // wiping first, --force on a module republished more than once
                // (e.g. after refreshMigrationTimestamps() already renamed its
                // migrations) accumulates stale renamed copies alongside the
                // fresh ones, so migrate picks up duplicate ALTER TABLE statements.
                File::deleteDirectory($destination);
            }

            File::copyDirectory($modulePath, $destination);

            $this->updateNamespace($destination, $moduleName);

            $this->refreshMigrationTimestamps($destination, $moduleName);

            $this->info("Published: {$moduleName}");
        }

        $this->publishAppStubs($requestedModules);
    }

    /**
     * A module's own migration may ALTER a table a base/vendor migration
     * creates (e.g. Permission's add_display_field ALTERs
     * spatie/laravel-permission's `permissions` table). Laravel runs pending
     * migrations in filename-timestamp order, and this module's migration
     * ships stamped with whatever date it was authored on — nothing
     * guarantees that predates the moment someone actually publishes the
     * vendor migration it depends on (it didn't: authored 2026-01-23,
     * first published to a real project on 2026-08-31). Re-stamping every
     * migration in the module with the current time at publish time — the
     * same trick Laravel's own publishesMigrations() uses for package
     * migration stubs — guarantees this module's migrations sort after
     * anything already sitting in database/migrations, as long as
     * prerequisite vendor migrations were published before this module (see
     * Instalation.md's ordering note for Permission). Multiple migrations
     * within one module keep their relative order — each gets the base
     * timestamp plus its index in seconds.
     *
     * The module name is folded into the new filename too, not just the
     * timestamp — Laravel's migrator identifies a migration by filename
     * alone (not full path), and two modules publishing within the same
     * second whose source files happened to share a name (Permission's and
     * Role's were both literally add_display_field.php) would otherwise
     * collide: one file silently shadows the other in the `migrations`
     * table, and the shadowed one is recorded as run without its up() ever
     * executing. Confirmed live — Role's column landed, Permission's didn't,
     * both under one `migrations` row.
     */
    protected function refreshMigrationTimestamps(string $modulePath, string $moduleName): void
    {
        $migrationsDir = $modulePath.'\\database\\migrations';

        if (! File::isDirectory($migrationsDir)) {
            return;
        }

        $files = collect(File::files($migrationsDir))
            ->filter(fn ($file) => preg_match('/^\d{4}_\d{2}_\d{2}_\d{6}_/', $file->getFilename()))
            ->sortBy(fn ($file) => $file->getFilename())
            ->values();

        $timestamp = now();
        $slug = strtolower($moduleName);

        foreach ($files as $file) {
            $rest = preg_replace('/^\d{4}_\d{2}_\d{2}_\d{6}/', '', $file->getFilename());
            $newName = $timestamp->format('Y_m_d_His').'_'.$slug.$rest;

            File::move($file->getPathname(), $migrationsDir.'\\'.$newName);

            $timestamp = $timestamp->addSecond();
        }
    }

    /**
     * App-level files a default module needs that can't live under
     * app/Nexus/Modules itself — e.g. the User module's #[Module]-attributed
     * entity is App\Models\User (Laravel's own auth config expects the model
     * at that conventional path), not app/Nexus/Modules/User/Models/User.php.
     * Published once per stub; becomes a normal file in the app afterwards,
     * never touched again without --force. The one exception: Laravel ships
     * its own stock app/Models/User.php in every fresh app, so a destination
     * that lacks the stub's 'marker' is treated as that stock file — it is
     * backed up to *.bak and replaced (otherwise the stub could never apply).
     */
    protected function publishAppStubs(array $requestedModules): void
    {
        $stubs = [
            'User' => [
                'stub' => dirname(__DIR__, 2).'\\src\\AppStubs\\User.php.stub',
                'destination' => app_path('Models\\User.php'),
                'marker' => 'UserModelTrait',
            ],
        ];

        foreach ($stubs as $moduleName => $stub) {
            if (! empty($requestedModules) && ! in_array($moduleName, $requestedModules)) {
                continue;
            }

            if (! File::exists($stub['stub'])) {
                continue;
            }

            if (File::exists($stub['destination']) && ! $this->option('force')) {
                $isStockFile = isset($stub['marker'])
                    && ! str_contains(File::get($stub['destination']), $stub['marker']);

                if (! $isStockFile) {
                    $this->warn('⚠️ '.basename($stub['destination']).' already exists — skipped');

                    continue;
                }

                File::copy($stub['destination'], $stub['destination'].'.bak');
                $this->warn('⚠️ '.basename($stub['destination']).' is the stock Laravel file — replaced, backup: '.basename($stub['destination']).'.bak');
            }

            File::copy($stub['stub'], $stub['destination']);

            $this->info('Published stub: '.basename($stub['destination']));
        }
    }

    protected function getRequestedModules(): array
    {
        $option = $this->option('module');

        if (! $option) {
            return [];
        }

        return array_map('trim', explode(',', $option));
    }

    protected function getVendorModulesPath(): string
    {
        return dirname(__DIR__, 2).'\\src\\Modules';
    }

    protected function updateNamespace(string $modulePath, string $moduleName): void
    {
        $files = File::allFiles($modulePath);

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $content = File::get($file->getPathname());

            $oldNamespace = 'Nodex\\Nexus\\Modules\\'.$moduleName;

            $newNamespace = 'App\\Nexus\\Modules\\'.$moduleName;

            $updated = str_replace($oldNamespace, $newNamespace, $content);

            File::put($file->getPathname(), $updated);
        }
    }
}
