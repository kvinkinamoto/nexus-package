<?php

namespace Nodex\Nexus\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Nodex\Nexus\Dto\ModuleDtos\DefaultModuleConfigurationDto;

class EntityCreating
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Model $model,
        public array &$data,
        public DefaultModuleConfigurationDto $moduleConfig
    ) {
    }
}
