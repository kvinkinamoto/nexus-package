<?php

namespace Nodex\Nexus\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One (module, key) => value row, backing Nodex\Nexus\Services\
 * DatabaseSettingsProvider. Deliberately not a Nexus admin module itself —
 * there's nothing to CRUD here directly; every module's own settings screen
 * (Livewire\ModuleSettingsForm) reads/writes through SettingsBuilder instead.
 */
class ModuleSetting extends Model
{
    protected $table = 'nexus_module_settings';

    protected $fillable = ['module_id', 'key', 'value'];

    protected $casts = [
        'value' => 'json',
    ];

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }
}
