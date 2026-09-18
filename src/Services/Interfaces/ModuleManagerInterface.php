<?php

namespace Nodex\Nexus\Services\Interfaces;

use Nodex\Nexus\Exeptions\DataNotImplemented;

interface ModuleManagerInterface
{
    /**
     * @throws DataNotImplemented
     */
    public function getDataByName(string $name);
}
