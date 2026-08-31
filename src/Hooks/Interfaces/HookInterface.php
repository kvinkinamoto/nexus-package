<?php

namespace Nodex\Nexus\Hooks\Interfaces;

use Illuminate\Database\Eloquent\Model;

interface HookInterface
{

    public function before(Model $model, array $newData, array $oldData = []): array;

    public function after(Model $model, array $newData, array $oldData = []): array;


}
