<?php

namespace Nodex\Nexus\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ModuleEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $moduleConfig;
    public $action;
    public $data;

    public function __construct($moduleConfig, $action, &$data = [])
    {
        $this->moduleConfig = $moduleConfig;
        $this->action = $action;
        $this->data = &$data;
    }
}
