## <h2 style="color:#ba363f">Settings Registry</h2>

The settings registry is a general-purpose mechanism that lets any module
or plugin declare its own settings (`#[Setting(...)]`) without a separate
migration or model. Unlike a full module, which has its own
table (one model → one database table), settings are stored in
a single shared table, `nexus_module_settings`, as a (module, key) →
value pair. In other words, adding a new setting means adding one attribute to a class, not
writing `php artisan make:migration`.

Any module that declares at least one `#[Setting(...)]` automatically gets
a `.../action/settings` screen and a row on the "All Settings" page — a separate
table for the module's settings is not needed.

## The `#[Setting(...)]` attribute

File: `src/Attributes/Setting.php`.

```php
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class Setting
{
    public function __construct(
        public readonly string $name,
        public readonly string $type,
        public readonly ?string $label = null,
        public readonly mixed $default = null,
        public readonly bool $required = false,
        public readonly array $options = [],
        public readonly bool $multiple = false,
        public readonly ?string $comment = null,
    ) {}
}
```

The attribute is applied to a class (`TARGET_CLASS`) and is repeatable (`IS_REPEATABLE`)
— meaning you can attach as many `#[Setting(...)]` as you like to a single class,
each one declaring one field.

| Parameter | Type | Required | Description |
| --- | --- | --- | --- |
| `name` | `string` | yes | The setting's key, unique within the module. Stored as `key` in `nexus_module_settings` and passed to `SettingsBuilder::get()/set()`. |
| `type` | `string` | yes | The field type for form rendering. In practice these occur: `string`, `text`, `boolean`, `integer`, `select`, `image` — `module-settings-form.blade.php` handles exactly these types. Any other type renders as a plain `<input type="text">` (the `@else` branch). |
| `label` | `?string` | no | The field's label. If not set, `SettingConfigDto` falls back to `ucfirst($name)`. The actual text shown on screen comes from the `{module}::translate.{lowercase label}` translation via `nexus_trans_label()` (the same convention used for regular `#[Field]`). |
| `default` | `mixed` | no | The default value returned by `SettingsBuilder::get()` as long as there's no row of its own in the database. |
| `required` | `bool` | no (`false`) | Whether the field is required when saving — `ModuleSettingsForm::save()` builds the `required`/`nullable` validation rule from this. |
| `options` | `array` | no (`[]`) | `value => label` pairs for `type: 'select'`. Ignored by other types. |
| `multiple` | `bool` | no (`false`) | The "multiple choice" flag in `SettingConfigDto` (`isMultiple`). As of now none of the real `#[Setting]` declarations in the project use it, and the form's blade template has no branch that accounts for it — the attribute is read (`AttributeSchemaReader::processSettingAttrs()` calls `$setting->multiple(...)`), but it has no visible effect in the current UI. |
| `comment` | `?string` | no | A hint shown below the field (`<p class="mt-1.5 text-xs text-gray-400">`). |

## How to attach settings to a module/plugin

It's enough to attach one or more `#[Setting(...)]` to a class that
`ModuleManager` already resolves as the module's configuration — either the module's Eloquent
model (`Models/{Name}.php`), or a separate `ModuleConfiguration.php` class for
modules without their own table. A real example is `SitemapCustomUrl`
(`app/Nexus/Modules/Sitemap/Models/SitemapCustomUrl.php`), where the module simultaneously
has its own CRUD table (`sitemap_custom_urls`) *and* three settings:

```php
#[Module(
    name: 'sitemap',
    label: 'Sitemap',
    icon: 'solar:map-point-wave-bold',
    group: 'Site',
    showInMenu: true,
    livewire: true,
)]
// ...інші атрибути модуля...
#[Setting(name: 'mode', type: 'select', label: 'Generation mode', default: 'multi', options: ['multi' => 'Multi file + index', 'single' => 'Single file'], required: true)]
#[Setting(name: 'base_url', type: 'string', label: 'Base URL override', comment: 'Overrides APP_URL for every generated URL. Leave blank to use APP_URL.')]
#[Setting(name: 'split_size', type: 'integer', label: 'Split size', default: 0, comment: 'Max URLs per file before splitting into chunks + a sub-index. 0 = off.')]
class SitemapCustomUrl extends Model
{
    // ...
}
```

An example of a module without its own model is `App\Nexus\Modules\Settings\ModuleConfiguration`
(site settings: `site_name`, `site_description`, `maintenance_mode`,
`logo`, `favicon`), built entirely on `#[Setting]` without any table
for the module itself.

Attributes are read by `AttributeSchemaReader::processSettingAttrs()`: each
`#[Setting]` is turned into a `SettingConfigDto` and placed into
`DefaultModuleConfigurationDto::$settings[$name]` — this is exactly the array that
`ModuleSettingsForm` and the "All Settings" page see.

## Where values are stored and how to read them in code

Values are stored in the `nexus_module_settings` table
(`database/migrations/2025_01_01_000000_create_nexus_tables.php`):

```php
Schema::create('nexus_module_settings', function (Blueprint $table) {
    $table->id();
    $table->foreignId('module_id')->constrained('nexus_modules')->onDelete('cascade');
    $table->string('key');
    $table->json('value')->nullable();
    $table->timestamps();
});
```

One row = one (module, key) pair; `value` is a `json` column, so
any scalar or structured PHP type can be written to it. The unique index
`(module_id, key)` was added by a later migration
(`2026_09_07_000000_add_unique_module_key_to_nexus_module_settings_table.php`)
— this is exactly what `updateOrCreate()` in the provider relies on.

Storage and reading go through two layers:

- `Nodex\Nexus\Services\Interfaces\SettingsProviderInterface` — the contract
  (`get`/`set`/`getAll`), bound in the container to
  `Nodex\Nexus\Services\DatabaseSettingsProvider` in `NexusServiceProvider`
  (`$this->app->bind(SettingsProviderInterface::class, DatabaseSettingsProvider::class)`).
  The application can replace this binding with its own provider the same way
  `MediaLibraryInterface` is overridden.
- `Nodex\Nexus\Services\SettingsBuilder` — a facade wrapper around the provider with
  caching (`Cache::rememberForever("nexus_settings_{module}_{key}")`) and
  giving `config('nexus::{module}.{key}')` priority over the stored value.

The package has **no** separate helper function like `nexus_setting()` —
throughout the project, values are read and written exclusively through the static
`SettingsBuilder` methods:

```php
use Nodex\Nexus\Services\SettingsBuilder;

// читання, з дефолтом
$mode = SettingsBuilder::get('sitemap', 'mode', 'multi');

// запис
SettingsBuilder::set('sitemap', 'split_size', 5000);
```

This is how it's used, for example, in `app/Nexus/Modules/Sitemap/Commands/GenerateSitemaps.php`
and `app/Nexus/Modules/Order/Services/OrderService.php`
(`SettingsBuilder::get('order', 'allow_guest_checkout', true)`).

⚠️ The `Cache::rememberForever()` cache is only invalidated for a specific
key (`SettingsBuilder::forget($module, $key)`, which is called inside
`set()`). There's no bulk `forget()` for all of a module's keys at once — the
`if ($key)` branch in `forget()` does nothing without `$key` (there's a leftover comment
`// This needs to be handled by the provider...`).

## The "All Settings" page

The admin topbar (`tailadmin/layouts/header.blade.php`, the "Settings" item in the
account dropdown) leads to `route('nexus.module.action', ['module' => 'settings', 'action' => 'index'])`.
This is the `index()` of the `App\Nexus\Modules\Settings` module — its own
`AdminController::index()` iterates over all **enabled** modules
(`Module::query()->where('is_enabled', true)`), resolves the
config for each via `ModuleManager::getModuleConfig($name)`, and keeps only the ones
where `$config->settings` is not empty. The result is sorted by menu item name
and rendered in `settings::overview`
(`app/Nexus/Modules/Settings/resources/views/overview.blade.php`) — a list of
cards "module → number of settings → Manage link".

It is a deliberate choice not to have one big form with all settings from all modules
at once: different modules have different access permissions and different field
semantics, so each row leads to its own screen
`route('nexus.module.action', ['module' => $name, 'action' => 'settings'])`,
which renders `Nodex\Nexus\Livewire\ModuleSettingsForm` for that specific
module.

## Routing: `action=>'settings'`, not `'edit'`

A separate `settings` action is intentionally used for settings, instead of
reusing `edit`:

```php
// NexusController::settings()
public function settings(FormRequest $request, Module $module, ?string $id = null)
{
    return view('nexus::'.config('nexus.template').'.pages.moduleSettingsLivewire', [
        'module' => $module,
    ]);
}
```

The reason (from the method's docblock): for a module bound to a model, `edit`
already means "edit a specific row by `id`" — a settings screen that shared
that action name would either conflict with that route, or (without `id`)
be misinterpreted by `ModuleForm::mount()` as a regular create/
edit row form. That's why the link to a module's settings screen is always
built as:

```php
route('nexus.module.action', ['module' => $moduleName, 'action' => 'settings'])
```

and not `'action' => 'edit'`. This works the same regardless of whether the
module has its own model at all (`App\Nexus\Modules\Settings` has no model and
relies on this same method).

Access permissions for the settings screen are also deliberately non-standard:
`ModuleSettingsForm::mount()` checks the `'edit'` permission (`ModuleManager::checkPermission('edit', ...)`),
rather than a separate `'settings'` permission — no module registers such a separate
permission, and "can edit the module" is considered a sufficient condition for
managing its settings.
