## <h2 style="color:#ba363f">Settings Registry</h2>

Реєстр налаштувань — це загальний механізм, який дозволяє будь-якому модулю
чи плагіну оголосити власні налаштування (`#[Setting(...)]`) без окремої
міграції чи моделі. На відміну від повноцінного модуля, який має власну
таблицю (одна модель → одна таблиця БД), налаштування зберігаються в
одній спільній таблиці `nexus_module_settings` як пара (module, key) →
value. Тобто додати новий setting — це додати один атрибут на клас, а не
писати `php artisan make:migration`.

Кожен модуль, що оголосив хоча б один `#[Setting(...)]`, автоматично отримує
екран `.../action/settings` і рядок на сторінці «All Settings» — окрема
таблиця для setting-ів модулю не потрібна.

## Атрибут `#[Setting(...)]`

Файл: `src/Attributes/Setting.php`.

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

Атрибут вішається на клас (`TARGET_CLASS`) і є повторюваним (`IS_REPEATABLE`)
— тобто на один клас можна повісити скільки завгодно `#[Setting(...)]`,
кожен оголошує одне поле.

| Параметр | Тип | Обов'язковий | Опис |
| --- | --- | --- | --- |
| `name` | `string` | так | Ключ налаштування, унікальний у межах модуля. Зберігається як `key` у `nexus_module_settings` і передається в `SettingsBuilder::get()/set()`. |
| `type` | `string` | так | Тип поля для рендеру форми. У реальному використанні зустрічаються: `string`, `text`, `boolean`, `integer`, `select`, `image` — саме ці типи обробляє `module-settings-form.blade.php`. Інший тип рендериться як звичайний `<input type="text">` (гілка `@else`). |
| `label` | `?string` | ні | Підпис поля. Якщо не задано, `SettingConfigDto` підставляє `ucfirst($name)`. Фактичний текст на екрані бере переклад `{module}::translate.{lowercase label}` через `nexus_trans_label()` (та сама конвенція, що й у звичайних `#[Field]`). |
| `default` | `mixed` | ні | Значення за замовчуванням, яке повертає `SettingsBuilder::get()`, поки в БД немає власного рядка. |
| `required` | `bool` | ні (`false`) | Чи обов'язкове поле при збереженні — `ModuleSettingsForm::save()` будує з цього правило валідації `required`/`nullable`. |
| `options` | `array` | ні (`[]`) | Пари `value => label` для `type: 'select'`. Ігнорується іншими типами. |
| `multiple` | `bool` | ні (`false`) | Прапорець "множинний вибір" у `SettingConfigDto` (`isMultiple`). На сьогодні жоден з реальних `#[Setting]`-описів у проєкті його не використовує, і blade-шаблон форми не має гілки, яка б його враховувала — атрибут читається (`AttributeSchemaReader::processSettingAttrs()` викликає `$setting->multiple(...)`), але видимого ефекту в поточному UI немає. |
| `comment` | `?string` | ні | Підказка під полем (`<p class="mt-1.5 text-xs text-gray-400">`). |

## Як підключити налаштування до модуля/плагіна

Достатньо повісити один або кілька `#[Setting(...)]` на клас, який
`ModuleManager` вже резолвить як конфігурацію модуля — це або Eloquent-модель
модуля (`Models/{Name}.php`), або окремий клас `ModuleConfiguration.php` для
модулів без власної таблиці. Реальний приклад — `SitemapCustomUrl`
(`app/Nexus/Modules/Sitemap/Models/SitemapCustomUrl.php`), де модуль одночасно
має власну CRUD-таблицю (`sitemap_custom_urls`) *і* три налаштування:

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

Приклад модуля без власної моделі — `App\Nexus\Modules\Settings\ModuleConfiguration`
(сайтові налаштування: `site_name`, `site_description`, `maintenance_mode`,
`logo`, `favicon`), побудований повністю на `#[Setting]` без жодної таблиці
під сам модуль.

Атрибути зчитує `AttributeSchemaReader::processSettingAttrs()`: кожен
`#[Setting]` перетворюється на `SettingConfigDto` і кладеться в
`DefaultModuleConfigurationDto::$settings[$name]` — саме цей масив і бачать
`ModuleSettingsForm` та сторінка «All Settings».

## Де зберігаються значення та як їх читати в коді

Значення зберігаються в таблиці `nexus_module_settings`
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

Один рядок = одна пара (модуль, ключ); `value` — `json`-колонка, тому туди
пишеться будь-який скалярний чи структурований PHP-тип. Унікальний індекс
`(module_id, key)` додано пізнішою міграцією
(`2026_09_07_000000_add_unique_module_key_to_nexus_module_settings_table.php`)
— саме на ньому тримається `updateOrCreate()` у провайдері.

Зберігання і читання йде через два шари:

- `Nodex\Nexus\Services\Interfaces\SettingsProviderInterface` — контракт
  (`get`/`set`/`getAll`), в контейнері прив'язаний до
  `Nodex\Nexus\Services\DatabaseSettingsProvider` у `NexusServiceProvider`
  (`$this->app->bind(SettingsProviderInterface::class, DatabaseSettingsProvider::class)`).
  Застосунок може підмінити прив'язку власним провайдером тим самим шляхом,
  яким перевизначається `MediaLibraryInterface`.
- `Nodex\Nexus\Services\SettingsBuilder` — фасад-обгортка над провайдером з
  кешуванням (`Cache::rememberForever("nexus_settings_{module}_{key}")`) і
  пріоритетом `config('nexus::{module}.{key}')` над збереженим значенням.

Окремого helper-функції на кшталт `nexus_setting()` у пакеті **немає** —
у всьому проєкті значення читають і пишуть виключно через статичні методи
`SettingsBuilder`:

```php
use Nodex\Nexus\Services\SettingsBuilder;

// читання, з дефолтом
$mode = SettingsBuilder::get('sitemap', 'mode', 'multi');

// запис
SettingsBuilder::set('sitemap', 'split_size', 5000);
```

Так це використовується, наприклад, у `app/Nexus/Modules/Sitemap/Commands/GenerateSitemaps.php`
та `app/Nexus/Modules/Order/Services/OrderService.php`
(`SettingsBuilder::get('order', 'allow_guest_checkout', true)`).

⚠️ Кеш через `Cache::rememberForever()` інвалідується лише по конкретному
ключу (`SettingsBuilder::forget($module, $key)`, який викликається всередині
`set()`). Масового `forget()` для всіх ключів модуля одразу немає — гілка
`if ($key)` у `forget()` без `$key` нічого не робить (лишений коментар
`// This needs to be handled by the provider...`).

## Сторінка «All Settings»

Топбар адмінки (`tailadmin/layouts/header.blade.php`, пункт «Settings» в
account dropdown) веде на `route('nexus.module.action', ['module' => 'settings', 'action' => 'index'])`.
Це `index()` модуля `App\Nexus\Modules\Settings` — його власний
`AdminController::index()` перебирає всі **увімкнені** модулі
(`Module::query()->where('is_enabled', true)`), для кожного резолвить
конфіг через `ModuleManager::getModuleConfig($name)` і залишає лише ті, у
яких `$config->settings` непорожній. Результат сортується за назвою пункту
меню і рендериться у `settings::overview`
(`app/Nexus/Modules/Settings/resources/views/overview.blade.php`) — список
карток "модуль → кількість налаштувань → посилання Manage".

Свідомо не робиться одна велика форма з усіма налаштуваннями всіх модулів
одразу: різні модулі мають різні права доступу і різний сенс полів,
тому кожен рядок веде на власний екран
`route('nexus.module.action', ['module' => $name, 'action' => 'settings'])`,
який рендерить `Nodex\Nexus\Livewire\ModuleSettingsForm` для конкретного
модуля.

## Роутинг: `action=>'settings'`, а не `'edit'`

Для налаштувань навмисно використовується окрема дія `settings`, а не
перевикористовується `edit`:

```php
// NexusController::settings()
public function settings(FormRequest $request, Module $module, ?string $id = null)
{
    return view('nexus::'.config('nexus.template').'.pages.moduleSettingsLivewire', [
        'module' => $module,
    ]);
}
```

Причина (з докблоку методу): для модуля з прив'язаною моделлю `edit` вже
означає «редагувати конкретний рядок за `id`» — екран налаштувань, що
поділяв би цю назву дії, або конфліктував би з цим маршрутом, або (без `id`)
хибно трактувався б `ModuleForm::mount()` як звичайна форма створення/
редагування рядка. Тому посилання на екран налаштувань модуля завжди
формується як:

```php
route('nexus.module.action', ['module' => $moduleName, 'action' => 'settings'])
```

а не `'action' => 'edit'`. Це працює однаково незалежно від того, чи має
модуль власну модель узагалі (`App\Nexus\Modules\Settings` моделі не має і
покладається на цей самий метод).

Права доступу до екрана налаштувань також узгоджені навмисно нестандартно:
`ModuleSettingsForm::mount()` перевіряє право `'edit'` (`ModuleManager::checkPermission('edit', ...)`),
а не окреме `'settings'`-право — жоден модуль такого окремого права не
реєструє, а «може редагувати модуль» вважається достатньою умовою для
керування його налаштуваннями.
