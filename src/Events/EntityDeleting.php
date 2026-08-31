<?php

namespace Nodex\Nexus\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Nodex\Nexus\Dto\ModuleDtos\DefaultModuleConfigurationDto;

class EntityDeleting
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Model $model,
        public DefaultModuleConfigurationDto $moduleConfig
    ) {
    }
}
