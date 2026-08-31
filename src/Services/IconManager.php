<?php

namespace Nodex\Nexus\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

class IconManager
{
    protected array $icons = [];
    protected string $template;

    public function __construct()
    {
        $this->template = Config::get('nexus.template', 'nexus');
        $this->loadCoreIcons();
    }

    /**
     * Load core icons.
     */
    protected function loadCoreIcons(): void
    {
        $path = __DIR__ . '/../resources/icons/' . $this->template . '.php';
        if (file_exists($path)) {
            $this->icons['nexus'] = require $path;
        }
    }

    /**
     * Load icons for a specific module.
     *
     * @param string $namespace
     * @param string $dir
     */
    public function loadModuleIcons(string $namespace, string $dir): void
    {
        $path = $dir . DIRECTORY_SEPARATOR . $this->template . '.php';
        if (file_exists($path)) {
            $this->icons[$namespace] = require $path;
        }
    }

    /**
     * Get icon by key.
     *
     * @param string|null $key
     * @param string|null $module
     * @param string|null $default
     * @return string
     */
    public function getIcon(?string $key, ?string $module = null, ?string $default = null): string
    {
        $key = $key ?? '';

        // 1. Explicit namespace lookup (e.g. "permission::user" or "nexus::edit")
        if (str_contains($key, '::')) {
            [$namespace, $iconKey] = explode('::', $key);
            $namespace = Str::lcfirst($namespace);

            return $this->icons[$namespace][$iconKey] ?? $this->icons[$namespace][$default] ?? $key;
        }

        // 2. Bare key lookup: current module -> nexus (core)
        $iconKey = $key;

        if ($module) {
            $moduleNamespace = Str::lcfirst($module);
            if (isset($this->icons[$moduleNamespace][$iconKey])) {
                return $this->icons[$moduleNamespace][$iconKey];
            }
        }

        return $this->icons['nexus'][$iconKey] ?? $this->icons['nexus'][$default] ?? $key;
    }
}
