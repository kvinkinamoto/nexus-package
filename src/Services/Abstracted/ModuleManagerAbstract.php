<?php

namespace Nodex\Nexus\Services\Abstracted;

use Illuminate\Database\Eloquent\Model;
use Nodex\Nexus\Exeptions\DataNotImplemented;
use Nodex\Nexus\Services\Interfaces\ModuleManagerInterface;

class ModuleManagerAbstract implements ModuleManagerInterface
{

    public function getDataByName(string $name)
    {
        // TODO: Implement getDataByName() method.
        throw new DataNotImplemented();
    }    
    
    public function storeCustomRelation(string $name, array $data, Model $model)
    {
        // TODO: Implement getDataByName() method.
        throw new DataNotImplemented();
    }
}
