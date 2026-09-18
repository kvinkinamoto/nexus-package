<?php

namespace Nodex\Nexus\Services;

use Illuminate\Support\Str;

/**
 * Checks a module's declared #[Module(requires: [...])] against which
 * modules are actually enabled right now. Purely informational — nothing
 * here blocks install/enable. See Attributes/Module.php for why.
 */
class ModuleDependencyChecker
{
    public function __construct(private ModuleRegistry $registry)
    {
    }

    /**
     * @return string[] Required module names that aren't currently enabled.
     *                   Empty when $moduleName has no unmet requirement
     *                   (including when it declares none at all).
     */
    public function getMissingDependencies(string $moduleName): array
    {
        $config = ModuleManager::getModuleConfig($moduleName);

        if (empty($config->requires)) {
            return [];
        }

        $enabledNames = $this->registry->getEnabledModules()
            ->pluck('name')
            ->map(fn ($name) => Str::lower($name))
            ->all();

        return array_values(array_filter(
            $config->requires,
            fn ($required) => !in_array(Str::lower($required), $enabledNames, true),
        ));
    }

    public function hasMissingDependencies(string $moduleName): bool
    {
        return !empty($this->getMissingDependencies($moduleName));
    }

    /**
     * @return array<string, string[]> Every enabled module that has at
     *                                 least one unmet requirement, keyed by
     *                                 module name.
     */
    public function getAllUnmetDependencies(): array
    {
        $result = [];

        foreach ($this->registry->getEnabledModules() as $module) {
            $missing = $this->getMissingDependencies($module['name']);
            if (!empty($missing)) {
                $result[$module['name']] = $missing;
            }
        }

        return $result;
    }
}
