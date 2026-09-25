<?php

namespace Nodex\Nexus\commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class PublishDependency extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nexus:resource:publish';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'publish Nexus resource';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(

    )
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return string
     */
    public function handle(): string
    {
        $this->createNecessaryDirectory();
        return 'output';
    }

    /**
     * @return void
     */
    private function createNecessaryDirectory(): void
    {
        Artisan::call('vendor:publish --tag=nexus-config', [], $this->getOutput());
        Artisan::call('vendor:publish --tag=nexus-resources-publish', [], $this->getOutput());
        Artisan::call('vendor:publish --tag=nexus-js', [], $this->getOutput());
        Artisan::call('vendor:publish --tag=nexus-lang', [], $this->getOutput());
        Artisan::call('vendor:publish --tag=nexus-theme', [], $this->getOutput());

        $this->wireTheme();

        $this->info("✔️ Публікація ресурсів завершено!");

    }

    /**
     * Idempotently hooks the published theme into the app's own Vite entries
     * and declares the npm packages the theme's JS imports.
     */
    private function wireTheme(): void
    {
        $css = resource_path('css/app.css');
        if (File::exists($css) && ! str_contains(File::get($css), 'nexus-theme.css')) {
            $content = File::get($css);
            $import = "@import './nexus-theme.css';";
            $updated = preg_replace("/^(@import\s+['\"]tailwindcss['\"];[ \t]*\r?\n)/m", "$1$import\n", $content, 1, $count);
            File::put($css, $count ? $updated : $content."\n$import\n");
            $this->info('✔️ resources/css/app.css: додано імпорт nexus-theme.css');
        }

        $js = resource_path('js/app.js');
        if (File::exists($js) && ! str_contains(File::get($js), 'nexus-theme')) {
            File::put($js, "import './nexus-theme.js';\n".File::get($js));
            $this->info('✔️ resources/js/app.js: додано імпорт nexus-theme.js');
        }

        $packageJson = base_path('package.json');
        if (File::exists($packageJson) && is_array($package = json_decode(File::get($packageJson), true))) {
            $added = [];

            foreach (['alpinejs' => '^3.17.1', '@alpinejs/collapse' => '^3.17.1'] as $name => $version) {
                if (! isset($package['dependencies'][$name]) && ! isset($package['devDependencies'][$name])) {
                    $package['dependencies'][$name] = $version;
                    $added[] = $name;
                }
            }

            if ($added) {
                ksort($package['dependencies']);
                File::put($packageJson, json_encode($package, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n");
                $this->info('✔️ package.json: додано '.implode(', ', $added).'. Виконайте: npm install && npm run build');
            }
        }
    }
}
