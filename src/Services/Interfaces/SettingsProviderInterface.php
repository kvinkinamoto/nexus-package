<?php

namespace Nodex\Nexus\Services\Interfaces;

use Illuminate\Support\Collection;

interface SettingsProviderInterface
{
    /**
     * Get a setting value.
     *
     * @param string $module
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $module, string $key, mixed $default = null): mixed;

    /**
     * Set a setting value.
     *
     * @param string $module
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function set(string $module, string $key, mixed $value): void;

    /**
     * Get all settings for a module.
     *
     * @param string $module
     * @return Collection
     */
    public function getAll(string $module): Collection;
}
