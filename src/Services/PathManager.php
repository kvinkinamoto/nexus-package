<?php

namespace Nodex\Nexus\Services;

use Illuminate\Support\Str;

class PathManager
{
    public function getModulesRoot(): string
    {
        return app_path('Nexus/Modules');
    }

    public function getUserModulesRoot(): string
    {
        return app_path('Nexus/UserModules');
    }

    public function getModulePath(string $name, bool $isUserModule = false): string
    {
        $root = $isUserModule ? $this->getUserModulesRoot() : $this->getModulesRoot();
        return $root . DIRECTORY_SEPARATOR . Str::ucfirst($name);
    }

    public function getModuleNamespace(string $name, bool $isUserModule = false): string
    {
        $prefix = $isUserModule ? 'App\\Nexus\\UserModules\\' : 'App\\Nexus\\Modules\\';
        return $prefix . Str::ucfirst($name);
    }

    public function getMigrationPath(string $name, bool $isUserModule = false): string
    {
        return $this->getModulePath($name, $isUserModule) . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'migrations';
    }

    public function getViewPath(string $name, bool $isUserModule = false): string
    {
        return $this->getModulePath($name, $isUserModule) . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views';
    }

    public function getRoutePath(string $name, bool $isUserModule = false): string
    {
        return $this->getModulePath($name, $isUserModule) . DIRECTORY_SEPARATOR . 'routes';
    }

    public function getTranslationPath(string $name, bool $isUserModule = false): string
    {
        return $this->getModulePath($name, $isUserModule) . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'lang';
    }

    public function getCommandPath(string $name, bool $isUserModule = false): string
    {
        return $this->getModulePath($name, $isUserModule) . DIRECTORY_SEPARATOR . 'Commands';
    }

    public function getIconPath(string $name, bool $isUserModule = false): string
    {
        return $this->getModulePath($name, $isUserModule) . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'icons';
    }
}
