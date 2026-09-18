<?php

namespace Nodex\Nexus\Services;

use Illuminate\Support\Facades\File;
use Nodex\Nexus\Models\Module as ModuleModel;

class DirectTranslationService
{
    public function __construct(
        protected ModuleRegistry $moduleRegistry,
        protected PathManager $pathManager
    ) {}

    /**
     * Get all translation groups (files) from all sources.
     */
    public function getGroups(): array
    {
        $groups = [];

        // 1. Global lang directory (check root and resources)
        $this->scanDirectory(base_path('lang'), null, $groups);
        $this->scanDirectory(resource_path('lang'), null, $groups);

        // 2. Modules
        $enabledModules = ModuleModel::where('is_enabled', 1)->get();
        foreach ($enabledModules as $module) {
            $regModule = $this->moduleRegistry->getModule($module->name);
            if (!$regModule) continue;

            $moduleLangPath = $regModule['path'] . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'lang';
            if (File::isDirectory($moduleLangPath)) {
                $this->scanDirectory($moduleLangPath, $module->name, $groups);
            }
        }

        // 3. Nexus Core (if applicable)
        $coreLangPath = base_path('vendor/nodex/nexus/src/resources/lang');
        if (File::isDirectory($coreLangPath)) {
            $this->scanDirectory($coreLangPath, 'nexus', $groups);
        }

        return $groups;
    }

    protected function scanDirectory(string $path, ?string $namespace, array &$groups): void
    {
        if (!File::isDirectory($path)) return;

        $locales = File::directories($path);
        foreach ($locales as $localePath) {
            $locale = basename($localePath);
            $files = File::allFiles($localePath);

            foreach ($files as $file) {
                if ($file->getExtension() !== 'php') continue;

                $groupName = $file->getBasename('.php');
                $id = $namespace ? "{$namespace}::{$groupName}" : $groupName;

                if (!isset($groups[$id])) {
                    $allPhrases = $this->getKeys($id);
                    $totalKeys = count($allPhrases);
                    $translatedCount = 0;
                    $activeLocales = $this->getActiveLocales();

                    foreach ($allPhrases as $key => $localeValues) {
                        $isComplete = true;
                        foreach ($activeLocales as $locale) {
                            if (!isset($localeValues[$locale]) || empty($localeValues[$locale])) {
                                $isComplete = false;
                                break;
                            }
                        }
                        if ($isComplete) $translatedCount++;
                    }

                    $groups[$id] = [
                        'id' => $id,
                        'name' => $groupName,
                        'namespace' => $namespace,
                        'full_id' => $id,
                        'total_keys' => $totalKeys,
                        'translated_keys' => $translatedCount,
                        'percentage' => $totalKeys > 0 ? round(($translatedCount / $totalKeys) * 100) : 100,
                    ];
                }
            }
        }
    }

    /**
     * Get all keys and their translations for a specific group.
     */
    public function getKeys(string $groupId): array
    {
        $parts = explode('::', $groupId);
        $namespace = count($parts) > 1 ? $parts[0] : null;
        $groupName = count($parts) > 1 ? $parts[1] : $parts[0];

        $langPaths = $this->getLangPaths($namespace);
        $activeLocales = $this->getActiveLocales();
        
        $allKeys = [];

        foreach ($activeLocales as $locale) {
            foreach ($langPaths as $path) {
                $filePath = $path . DIRECTORY_SEPARATOR . $locale . DIRECTORY_SEPARATOR . $groupName . '.php';
                if (File::exists($filePath)) {
                    $translations = File::getRequire($filePath);
                    if (is_array($translations)) {
                        $this->flattenArray($translations, $allKeys, $locale);
                    }
                }
            }
        }

        return $allKeys;
    }

    protected function flattenArray(array $array, array &$result, string $locale, string $prefix = ''): void
    {
        foreach ($array as $key => $value) {
            $fullKey = $prefix ? "{$prefix}.{$key}" : $key;
            if (is_array($value)) {
                $this->flattenArray($value, $result, $locale, $fullKey);
            } else {
                $result[$fullKey][$locale] = $value;
            }
        }
    }

    public function saveKey(string $id, string $key, array $translations): void
    {
        $parts = explode('::', $id);
        $namespace = count($parts) > 1 ? $parts[0] : null;
        $groupName = count($parts) > 1 ? $parts[1] : $parts[0];

        foreach ($translations as $locale => $value) {
            if (is_null($value) || $value === '') {
                continue;
            }

            // Determine where to save
            if (!$namespace) {
                // Global lang/ (Prefer root lang/ if it exists)
                if (File::isDirectory(base_path('lang'))) {
                    $path = base_path('lang');
                } else {
                    $path = lang_path();
                }
            } else {
                $regModule = $this->moduleRegistry->getModule($namespace);
                // If it's a module in app/Nexus/Modules, edit directly. 
                // Otherwise (core or vendor packages), use lang/vendor override.
                if ($regModule && str_contains($regModule['path'], base_path('app'))) {
                    $path = $regModule['path'] . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'lang';
                } else {
                    $path = lang_path("vendor/{$namespace}");
                }
            }

            $dirPath = $path . DIRECTORY_SEPARATOR . $locale;
            if (!File::isDirectory($dirPath)) {
                File::makeDirectory($dirPath, 0755, true);
            }

            $filePath = $dirPath . DIRECTORY_SEPARATOR . $groupName . '.php';
            $data = File::exists($filePath) ? File::getRequire($filePath) : [];
            
            if (!is_array($data)) $data = [];

            $this->setArrayValue($data, $key, $value);

            $content = "<?php\n\nreturn " . var_export($data, true) . ";\n";
            $content = str_replace('array (', '[', $content);
            $content = str_replace(')', ']', $content);

            File::put($filePath, $content);
        }
    }

    protected function setArrayValue(array &$array, string $key, mixed $value): void
    {
        $keys = explode('.', $key);
        while (count($keys) > 1) {
            $key = array_shift($keys);
            if (!isset($array[$key]) || !is_array($array[$key])) {
                $array[$key] = [];
            }
            $array = &$array[$key];
        }
        $array[array_shift($keys)] = $value;
    }

    protected function getLangPaths(?string $namespace): array
    {
        $paths = [];
        if (!$namespace) {
            // Global paths (check both root and resources)
            if (File::isDirectory(base_path('lang'))) {
                $paths[] = base_path('lang');
            }
            if (File::isDirectory(resource_path('lang'))) {
                $paths[] = resource_path('lang');
            }
        } else {
            // 1. Check the source (module or core) - LOWER PRIORITY
            if ($namespace === 'nexus') {
                $paths[] = base_path('vendor/nodex/nexus/src/resources/lang');
            } else {
                $regModule = $this->moduleRegistry->getModule($namespace);
                if ($regModule) {
                    $paths[] = $regModule['path'] . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'lang';
                }
            }

            // 2. Check for overrides in lang/vendor - HIGHER PRIORITY (wins)
            $overridePath = lang_path("vendor/{$namespace}");
            if (File::isDirectory($overridePath)) {
                $paths[] = $overridePath;
            }
        }
        return array_unique($paths);
    }

    protected function getActiveLocales(): array
    {
        return \App\Nexus\Modules\Language\Models\Language::query()->isPublished()->pluck('code')->toArray() ?: ['en', 'uk'];
    }
}
