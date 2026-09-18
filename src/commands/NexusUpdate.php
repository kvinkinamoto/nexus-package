<?php

namespace Nodex\Nexus\commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class NexusUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nexus:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Nexus package';

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
        exec('composer update nodex/nexus');

        Artisan::call('nexus:resource:publish', [], $this->getOutput());

        $this->info("✔️ Update Nexus ready!");
    }
}
