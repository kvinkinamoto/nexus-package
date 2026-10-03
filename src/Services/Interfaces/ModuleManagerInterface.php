<?php

namespace Nodex\Nexus\Services\Interfaces;

use Nodex\Nexus\Exceptions\DataNotImplemented;

interface ModuleManagerInterface
{
    /**
     * @throws DataNotImplemented
     */
    public function getDataByName(string $name);
}
