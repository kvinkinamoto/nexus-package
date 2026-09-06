<?php

namespace Nodex\Nexus\Livewire;

use Livewire\Component;
use Nodex\Nexus\Dto\ModuleDtos\DefaultModuleConfigurationDto;
use Nodex\Nexus\Dto\ModuleDtos\SettingConfigDto;
use Nodex\Nexus\Models\Module;
use Nodex\Nexus\Services\ModuleManager;
use Nodex\Nexus\Services\SettingsBuilder;

/**
 * Generic editor for whatever #[Setting(...)] attributes a module declares
 * (see Attributes\Setting) — one component for every module's settings
 * screen, the same "one component parameterized by module name" shape as
 * ModuleTable/ModuleForm (see ModuleForm's class docblock). Persists through
 * SettingsBuilder/DatabaseSettingsProvider: one (module, key) row each, never
 * a migration or model column per setting.
 *
 * Unlike ModuleForm there is no bound Eloquent model and no create/delete —
 * a module's setting set is fixed by its own class attributes, and saving
 * always upserts by key, so there's nothing to guard against duplicate rows
 * the way singleton CRUD rows need (contrast App\Nexus\Modules\Modules\
 * Models\Module's booted() guard).
 */
class ModuleSettingsForm extends Component
{
    public string $moduleName;

    public array $data = [];

    public function mount(string $moduleName): void
    {
        $this->moduleName = $moduleName;

        abort_unless(ModuleManager::checkPermission('edit', $this->resolveModule()), 403);

        foreach ($this->settings() as $key => $setting) {
            $this->data[$key] = SettingsBuilder::get($moduleName, $key, $setting->default);
        }
    }

    /** @return array<string, SettingConfigDto> */
    public function settings(): array
    {
        return $this->resolveModuleConfig()->settings;
    }

    public function clearImage(string $key): void
    {
        $this->data[$key] = null;
    }

    public function save(): void
    {
        $settings = $this->settings();

        $this->validate(
            collect($settings)->mapWithKeys(
                fn (SettingConfigDto $setting, string $key) => ["data.{$key}" => $setting->isRequired ? 'required' : 'nullable']
            )->all()
        );

        foreach ($settings as $key => $setting) {
            $value = $this->data[$key] ?? null;

            if ($setting->type === 'boolean') {
                $value = (bool) $value;
            }

            SettingsBuilder::set($this->moduleName, $key, $value);
        }

        session()->flash('alert_message', __('nexus::translate.alert.update_success'));
        session()->flash('alert_type', 'success');

        $this->redirect(route('nexus.module.action', ['module' => $this->moduleName, 'action' => 'edit']));
    }

    private function resolveModule(): Module
    {
        return Module::findByName($this->moduleName) ?? abort(404);
    }

    private function resolveModuleConfig(): DefaultModuleConfigurationDto
    {
        return ModuleManager::getModuleConfig($this->moduleName);
    }

    public function render()
    {
        return view('nexus::'.config('nexus.template').'.livewire.module-settings-form', [
            'settings' => $this->settings(),
            'module' => (object) ['name' => $this->moduleName],
        ]);
    }
}
