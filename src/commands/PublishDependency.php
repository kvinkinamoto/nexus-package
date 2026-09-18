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

        $this->info("✔️ Публікація ресурсів завершено!");

    }
}
