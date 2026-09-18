<?php

namespace Nodex\Nexus\Services\Dispatchers;

use Illuminate\Support\Facades\Event;
use Nodex\Nexus\Events\ModuleEvent;
use Nodex\Nexus\Models\Module;

class EventDispatcher
{
    public static function dispatchModuleEvent(array $moduleConfig, string $action, &$data)
    {
        $event = new ModuleEvent($moduleConfig, $action, $data);
        Event::dispatch($event);

        if (isset($moduleConfig['plugins'])) {
            foreach ($moduleConfig['plugins'] as $plugin) {
                if (class_exists($plugin['listener'])) {
                    $listener = new $plugin['listener']();
                    $listener->handle($event);
                }
            }
        }
    }
}
