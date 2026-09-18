<?php

namespace Nodex\Nexus\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TableDataPrepared
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $moduleName,
        public &$data
    ) {
    }
}
