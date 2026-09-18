<?php

namespace Nodex\Nexus\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ModuleInstalled
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $moduleName
    ) {
    }
}
