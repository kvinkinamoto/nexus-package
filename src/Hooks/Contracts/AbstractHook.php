<?php

namespace Nodex\Nexus\Hooks\Contracts;

use Illuminate\Database\Eloquent\Model;
use Nodex\Nexus\Hooks\Interfaces\HookInterface;

abstract class AbstractHook implements HookInterface
{
    public function before(Model $model, array $newData, array $oldData = []): array
    {
        return [];
    }

    public function after(Model $model, array $newData, array $oldData = []): array
    {
        return [];
    }
}