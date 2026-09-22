# Архітектура пакета Nodex\Nexus

Цей документ — про внутрішню "сантехніку" самого пакета `nodex/nexus`
(`packages/nodex/nexus`): як `NexusServiceProvider` піднімає застосунок, як
влаштований конфіг, кеш маніфесту модулів, Blade-директиви, система шаблонів
та реєстрація типів полів. Як писати модуль/плагін/віджет — це окремі
документи ([modules.md](modules.md), [plugins.md](plugins.md), [widgets.md](widgets.md)),
тут про це немає.

Джерело істини — сам код, насамперед `src/NexusServiceProvider.php`. Усі
твердження нижче звірені з реальними файлами; де впевненості немає, це прямо
сказано.

## 1. Послідовність завантаження (`NexusServiceProvider`)

### `register()`

Порядок важливий: пізніші кроки покладаються на те, що сінглтони з попередніх
кроків уже існують у контейнері.

1. `require_once __DIR__.'/helpers/functions.php'` — глобально стають
   доступні `nexus_filter()`/`nexus_action()`.
2. Біндяться як `singleton()` майже всі основні сервіси пакета:
   `PathManager`, `ModuleRegistry`, `ModuleManager`, `ModuleManifestCache`,
   `FormBuilder`, `ModuleServiceForAdminPanel`, `IconManager`, `HookManager`,
   `RelationRegistrar`, `ModuleDependencyChecker`, `PluginManager`,
   `AttributeSchemaReader`, `DirectTranslationService`, `FieldTypeRegistry`,
   `FieldVisibilityEvaluator`, `WidgetRegistry`, `BlockTypeRegistry`,
   `DashboardLayoutResolver`, `AdminDashboardRenderer`, `FrontWidgetRenderer`,
   `TemplateTypeResolver`, `NexusRuleCollector`.
3. `MediaLibraryInterface` біндиться через `bind()` (не `singleton()`) на
   `SpatieMediaLibraryService` — застосунок може перевизначити цей бінд у
   власному провайдері (який завантажується після `NexusServiceProvider`), і
   перевизначення просто виграє, без будь-якої спеціальної точки розширення
   з боку Nexus:

   ```php
   $this->app->bind(
       MediaLibraryInterface::class,
       SpatieMediaLibraryService::class,
   );
   ```
4. Так само `bind()`-иться `SettingsProviderInterface` → `DatabaseSettingsProvider`
   (сховище для `#[Setting(...)]`).
5. Якщо клас `Faker\Generator` доступний — реєструється `FakerGenerator`
   singleton із кастомним `FakerImageProvider`.

`PluginManager::autoDiscover()`/`registerAll()` у `register()` **не
викликаються** — вони перенесені у `boot()` (див. розділ "Готчі" нижче), і
коментар прямо в коді пояснює чому.

### `boot()`, у фактичному порядку джерела

1. **Discovery та реєстрація плагінів** (`PluginManager::autoDiscover()` +
   `registerAll()`), обгорнуті в `try/catch`: у `app.debug` виняток
   прокидається далі, інакше — `report($e)` і мовчазне проковтування.
2. `Model::shouldBeStrict(! $this->app->isProduction())`.
3. `mergeConfig()` — `mergeConfigFrom(config/nexus.php, 'nexus')`.
4. `publish()` — реєстрація publishable-груп (таблиця нижче).
5. `loadMigrationsFrom(__DIR__.'/database/migrations')` пакета +
   `registerDefaultApiRateLimiter()` (дефолтний rate-лімітер `'api'`, тільки
   якщо застосунок ще не визначив свій — інакше `throttleApi()` без власного
   `RateLimiter::for('api', ...)` впав би з `MissingRateLimiterException`).
6. Реєструються три Blade-директиви: `@position`, `@nexusForm`,
   `@nexusBlocks` (розділ 4).
7. `registerValidationRulesFilter()` — хук на
   `Illuminate\Contracts\Validation\Factory::resolver()`.
8. Визначається список модулів для discovery: поза консоллю — з
   `ModuleManifestCache::get()` (якщо файл кешу існує), інакше —
   `ModuleRegistry::getEnabledModules()`; у консолі — завжди **всі** модулі
   через `ModuleRegistry::getAllModules()` (щоб `migrate:fresh` та подібні
   команди бачили все, незалежно від `is_enabled`).
9. По черзі: `loadMigration()` → `loadView()` → `loadTranslation()` →
   `loadIcon()` → `loadRoute()` → `loadEvents()` →
   `Event::listen(ModuleInstalled::class, SendModuleInstalledNotification::class)`
   → `loadRelations()` → `loadFieldTypes()` →
   `registerBuiltInFieldTypeAliases()` → `registerBuiltInFieldTypeDefaultRules()`
   → `loadWidgets()` → `loadLivewireComponents()` (реєструє три Livewire-компоненти:
   `nexus-module-table`, `nexus-module-form`, `nexus-module-settings-form`).
10. `viewCompose()` — вішає `View::composer()` на
    `nexus::{template}.layouts.adminpanel` (сайдбар-меню +
    `moduleMissingDependencies`), плюс власні `$config->composers` кожного
    модуля.
11. У консолі — `registerSeeders()`.
12. `runCommand()` — реєстрація артизан-команд пакета й модулів.
13. **Boot плагінів** (`PluginManager::bootAll()`), той самий `try/catch`
    з `app.debug`-гейтом, що й у кроці 1.
14. Фінальна точка реєстрації типів полів: подія `FieldTypesRegistering` +
    `nexus_action('nexus.field_types.register', $fieldTypeRegistry)`.

```php
// NexusServiceProvider::boot(), фрагмент — три поточні реєстрації типів полів
$this->loadFieldTypes();                       // 2: модульні FieldTypes/*.php
$this->registerBuiltInFieldTypeAliases();
$this->registerBuiltInFieldTypeDefaultRules();
...
$fieldTypeRegistry = $this->app->make(FieldTypeRegistry::class);
event(new FieldTypesRegistering($fieldTypeRegistry));                 // 3a
nexus_action('nexus.field_types.register', $fieldTypeRegistry);       // 3b
```

## 2. Конфіг: `config/nexus.php`

### Ключі пакетного файлу (`packages/nodex/nexus/src/config/nexus.php`)

| Ключ | Призначення |
| --- | --- |
| `template` | Активна адмін-тема (`env('ZENTARA_TEMPLATE', 'tailadmin')`) — розділ 5. |
| `admin_prefix` | Префікс адмін-роутів (`env('ADMIN_PREFIX', 'admin')`). |
| `admin_middleware` | Middleware-стек адмінки: `['web', 'auth', NexusAdminMiddleware::class]` + закоментований приклад для app-специфічного middleware (типу локалізації). |
| `api_prefix` | Префікс API-роутів (`env('API_PREFIX', 'api')`). |
| `api_middleware` | Middleware-стек API: `['api']`. |
| `table.pagination.per_page_options` / `default_per_page` | Опції пагінації в таблицях модулів. |
| `permissions.generate_default` | Чи генерувати дефолтні permission-и для модуля. |
| `toast.enabled` / `toast.delay` | Поведінка toast-сповіщень в адмінці. |
| `widget_template_map` | Мапа `route name → template type` для `@position`/фронт-віджетів (див. `TemplateTypeResolver`). |
| `dashboard.default` | Впорядкований список widget-ключів (`#[Widget(name:)]`) для дашборду на свіжій інсталяції без рядків у `nexus_dashboard_layouts`. |
| `media_library.enabled` | Вмикає/вимикає `#[Field(type: 'gallery')]` (стаб-повідомлення замість поля, якщо `false`). |
| `plugins.disabled` | Список FQCN плагінів, які `PluginManager::autoDiscover()` має пропустити. |

Ключі `nexus.graphql_middleware`, `nexus.graphql_prefix` у пакетному файлі
**відсутні навмисно** — GraphQL є платним app-level плагіном
(`app/Nexus/Plugins/GraphQL`), не частиною цього репозиторію (див.
`../README.md`), тож ці ключі живуть лише в кореневому `config/nexus.php`.

### Дубльований конфіг-файл (root vs package)

`packages/nodex/nexus/src/config/nexus.php` (дефолти пакета) і опублікована
копія `config/nexus.php` у корені застосунку — два різні файли, і
`mergeConfigFrom()` у `boot()` **домальовує лише ті ключі, яких немає в
кореневому файлі**; жодного наявного там ключа він не перезаписує. Тобто для
будь-якого ключа, який є в обох файлах, реально діє значення з кореневого —
перевірити можна командою:

```bash
php artisan config:show nexus.dashboard
```

Станом на зараз обидва файли звірені й синхронізовані для спільних ключів
(`dashboard.default`, `media_library`, `plugins.disabled` приведені до
однакових значень; `admin_middleware` в пакеті має закоментований приклад
замість активного app-специфічного `SetAdminLocale::class`, який лишається
тільки в корені). `graphql_prefix`/`graphql_middleware` живуть виключно в
корені — свідомо, не помилка синхронізації.

⚠️ **Правило для мейнтейнерів**: якщо додаєте чи змінюєте будь-який ключ
`nexus.*`, який не є app-специфічним — редагуйте **обидва** файли
(`packages/nodex/nexus/src/config/nexus.php` і кореневий `config/nexus.php`),
і перевіряйте, яке значення реально активне, через `config:show`. Новий
ключ, якого ще немає в кореневому файлі, підхопиться нормально через
`mergeConfigFrom()` — саме тому пастку легко не помітити з першого разу: усе
працює, поки ключ новий.

## 3. Кеш маніфесту модулів (`bootstrap/cache/nexus-modules.php`)

`ModuleManifestCache` (`src/Services/ModuleManifestCache.php`) компілює
результат дискового сканування кожного модуля (views/routes/translations/
icons/commands/listeners/relations/scopes/fieldTypes/widgets) в один
PHP-масив, записаний у `bootstrap/cache/nexus-modules.php`:

```php
/**
 * Compiles the per-module filesystem discovery that NexusServiceProvider::boot()
 * would otherwise redo on every single request (is_dir/is_file checks for views,
 * routes, translations, icons; File::files()+class_exists for commands;
 * File::files()+ReflectionMethod for listeners) into one PHP array file.
 *
 * Purely opt-in: as long as the cache file doesn't exist, NexusServiceProvider
 * falls back to its original live-scanning behavior unchanged.
 */
```

Навіщо він існує: без кешу `NexusServiceProvider::boot()` на **кожен**
HTTP-запит робить `is_dir`/`is_file`-перевірки, читає директорії й рефлексує
класи для кожного увімкненого модуля — суто продуктивність.

Ключові властивості:

- Читається лише поза консоллю (`! $this->app->runningInConsole()`) — команди
  (`migrate`, тести тощо) завжди сканують диск наживо, щоб бачити щойно додані
  модулі без перекомпіляції кешу.
- Якщо файл кешу відсутній — `ModuleManifestCache::get()` повертає `null`, і
  `NexusServiceProvider` мовчки падає назад на живе сканування, поведінка не
  змінюється.
- Список увімкнених модулів з кешу все одно фільтрується одним живим SQL-запитом
  до `nexus_modules` (`enabledModulesFromManifest()`), бо "увімкнено/вимкнено" —
  це перемикач у БД, а не файлова структура.

Керування командами:

```bash
php artisan nexus:module:cache   # скомпілювати bootstrap/cache/nexus-modules.php
php artisan nexus:module:clear   # видалити його, повернутись до живого сканування
```

Якщо щось щойно додане під `app/Nexus/**` (модуль, тип поля, віджет,
listener, файл перекладу) не з'являється — перше, що варто перевірити,
це чи існує файл кешу.

## 4. Blade-директиви

Усі три реєструються прямо в `NexusServiceProvider::boot()` і поділяють один
принцип: **ніколи не кидають помилку через відсутні дані**, а мовчки
рендерять пусте місце.

### `@position('name')` / `@position('name', $templateType)`

```php
Blade::directive('position', function (string $expression) {
    $args = array_map('trim', explode(',', $expression, 2));
    $positionArg = $args[0];
    $templateTypeArg = $args[1] ?? null;

    $templateTypeExpr = $templateTypeArg !== null
        ? $templateTypeArg
        : 'app(\Nodex\Nexus\Services\Widgets\TemplateTypeResolver::class)->resolve()';

    return "<?php echo app(\Nodex\Nexus\Services\Widgets\FrontWidgetRenderer::class)->render({$positionArg}, {$templateTypeExpr}); ?>";
});
```

Рендерить усі активні `WidgetAssignment` для заданої позиції через
`FrontWidgetRenderer`. Якщо `templateType` не передано — визначається через
`TemplateTypeResolver::resolve()` (route-параметр → дефолт роута →
`config('nexus.widget_template_map')` за назвою роута → `'default'`).

### `@nexusForm('contact-slug')`

```php
Blade::directive('nexusForm', function (string $expression) {
    return "<?php \$__nexusForm = \Nodex\Nexus\Modules\Form\Models\Form::query()->where('slug', {$expression})->where('is_active', true)->first(); if (\$__nexusForm) { echo view('form::public.form', ['form' => \$__nexusForm])->render(); } ?>";
});
```

Вставляє публічну форму подання (модуль `Form`) за слагом. Невідомий чи
неактивний слаг — просто нічого не рендерить.

### `@nexusBlocks($page->blocks)`

```php
Blade::directive('nexusBlocks', function (string $expression) {
    return "<?php foreach (({$expression}) as \$__nexusBlock) { \$__nexusBlockView = 'nexus::public.block_types.' . \$__nexusBlock->type; if (\Illuminate\Support\Facades\View::exists(\$__nexusBlockView)) { echo view(\$__nexusBlockView, ['block' => \$__nexusBlock, 'data' => (object) (\$__nexusBlock->data ?? [])])->render(); } } ?>";
});
```

Рендерить упорядковану колекцію блоків (наприклад, `PageBlock`), для кожного
резолвлячи `nexus::public.block_types.{type}` за `View::exists()`. Блок
невідомого типу пропускається — решта блоків сторінки все одно
відмальовується.

## 5. Система шаблонів (tailadmin)

Активна тема визначається ключем `config('nexus.template')`
(`env('ZENTARA_TEMPLATE', 'tailadmin')`). Значення підставляється в шлях
в'юхи буквально в десятках місць пакета, наприклад:

```php
// Http/Controllers/NexusController.php
return view('nexus::'.config('nexus.template').'.pages.dashboard', [...]);

// Livewire/ModuleTable.php
return view('nexus::'.config('nexus.template').'.livewire.module-table', [...]);
```

`viewCompose()` в `NexusServiceProvider` теж прив'язаний саме до
`nexus::{template}.layouts.adminpanel` — нова тема повинна мати такий файл,
щоб отримати `menus`/`moduleMissingDependencies`.

**Історична примітка**: на диску є лише одна повна тема з Blade-в'юхами —
`src/resources/views/tailadmin/**` (поряд ще спільні `components/`,
`partials/`, `public/`). Коментар у самому конфігу натякає на історію:

```php
'template' => env('ZENTARA_TEMPLATE', 'tailadmin'), // tailadmin (nexus theme archived, see resources/views/nexus/ removed in Stage 2)
```

Раніше існувала ще й тема `nexus`, яку архівували на "Stage 2" й видалили з
`resources/views/nexus/`. Тема `adminlte` пройшла той самий шлях і на момент
першого проходу цієї документації лишалась мертвим артефактом: Blade-шар
відсутній, а `src/resources/publish/adminlte/**` — 99 МБ статичного
CSS/JS/img класичної теми AdminLTE — публікувався в `public/` попри те, що
рендерити цю тему було нічим (`ZENTARA_TEMPLATE=adminlte` зламав би рендер
на кожному екрані). Каталог **видалено повністю**; два вендорні JS/CSS-файли
з нього, які реально використовувала тема `tailadmin`
(`plugins/dropzone/dropzone.js` — drag-and-drop для полів-зображень,
`plugins/jquery-colorbox/example1/colorbox.css` — стилі логін-сторінки),
перенесені в `src/resources/publish/packages/{dropzone,jquery-colorbox}` і
посилання на них у в'юхах оновлені. `config('nexus.template')` тепер
фактично підтримує лише `tailadmin`; додавання іншої теми знову вимагатиме
власного дерева `resources/views/{name}/layouts/adminpanel.blade.php` і
решти файлів за тією ж конвенцією.

## 6. Реєстрація типів полів (`FieldTypeRegistry`)

`FieldTypeRegistry` (`src/Services/FieldTypeRegistry.php`) — єдина точка
резолву того, як рендериться `#[Field(type: ...)]`, спільна для обох
диспетчерів (legacy `templates/sections/_field.blade.php` і Livewire
`livewire/field_types/dispatch.blade.php`). Порядок резолву (з докблоку
класу), однаковий для обох:

1. `{module}::admin.field_types.{type}` (legacy) або
   `{module}::admin.livewire_field_types.{type}` (Livewire) — власне
   перевизначення модуля, завжди виграє, перевіряється самим диспетчером
   ще до звернення до реєстру.
2. Цей реєстр — `registerView()` / `registerClass()` / `registerCallback()`.
3. Вбудована в'юха за конвенційним шляхом типу
   (`.../templates.field_types.{type}` чи `.../livewire.field_types.{type}`) —
   резолвиться через `View::exists()`, тобто вбудований тип узагалі не
   потребує реєстрації в цьому класі, достатньо файлу за конвенцією.
4. `.../field_types.unknown` (legacy) чи `.../field_types/unsupported`
   (Livewire) — банер-заглушка, якщо нічого з вищого не спрацювало.

### Три точки реєстрації, і коли кожна відбувається

Підтверджено в `NexusServiceProvider::boot()`:

1. **`loadFieldTypes()`** — сканує `FieldTypes/`-папку кожного увімкненого
   модуля (`ModuleManifestCache::discoverFieldTypes()` або жива версія того
   самого сканування) і реєструє кожен знайдений клас-рендерер через
   `$registry->registerClass($fieldType['type'], $fieldType['class'])`.
   Тип реєструється як `camelCase(ім'я файлу)`.
2. **Подія `FieldTypesRegistering`** — фінальний крок `boot()`, вже після
   `loadFieldTypes()` і після boot-фази всіх плагінів.
3. **Дія `nexus_action('nexus.field_types.register', $fieldTypeRegistry)`** —
   одразу за подією, той самий реєстр, без потреби створювати клас-плагін;
   зазвичай саме цим шляхом плагіни `#[Action(hook: 'nexus.field_types.register')]`
   реєструють/перевизначають типи.

Обидва останні пункти виконуються **в кінці `boot()`** — тобто вже після
того, як всі модульні `FieldTypes/`-папки завантажені (крок 1) і після
`PluginManager::bootAll()`. Це дає плагінам можливість перевизначити тип,
зареєстрований модулем.

`registerBuiltInFieldTypeAliases()` (аліаси `editor→text`, `date→birthday`)
і `registerBuiltInFieldTypeDefaultRules()` (базові правила валідації за
типом поля, наприклад `'email' => ['email']`) виконуються між кроком 1 і
кроками 2–3 — вони не є окремою "точкою реєстрації типів" у сенсі
докблоку `FieldTypeRegistry`, а доповнюють вже зареєстровані типи алісами й
дефолтними правилами валідації.

### Чи є пастка з БД, як у плагінів?

Явних ознак того, що `FieldTypeRegistry`/`loadFieldTypes()` потребують БД,
у коді немає — реєстрація типів працює виключно з файловою системою й
рефлексією класів (`class_exists`, `is_subclass_of`), звернень до Eloquent
чи `DB::` тут не знайдено. На відміну від `PluginManager::autoDiscover()`
(розділ 7), реєстрація типів полів не має відомої пастки з доступністю БД.

## 7. Готчі для мейнтейнерів

### 7.1. `register()` vs `boot()` і доступність БД — `PluginManager`

Найбільш задокументована й найважливіша пастка в самому файлі провайдера.
`PluginManager::autoDiscover()`/`registerAll()` викликаються з `boot()`, а
не з `register()` — навмисно, з детальним поясненням прямо в коді:

```php
// Auto-discover and register plugins. Deliberately in boot(), not
// register(): PluginManager::isPluginEnabled() queries the
// nexus_plugins table, and Eloquent's connection resolver isn't set
// yet during register() — every provider's register() runs before
// any provider's boot(), including Laravel's own
// DatabaseServiceProvider. Running this from register() meant every
// DB lookup there threw "Call to a member function connection() on
// null", was swallowed by the catch below, and silently fell back to
// "enabled" every single time — so disabling a plugin from the admin
// screen had no effect on actual discovery, only on what the list
// page displayed.
```

Іншими словами: `register()` усіх сервіс-провайдерів (включно з
Laravel-івським `DatabaseServiceProvider`) виконується **до** `boot()`
будь-якого з них — тож звернення до БД із `register()` гарантовано провалиться
з `Call to a member function connection() on null`. Якщо плануєте додати ще
один DB-залежний механізм discovery (наприклад, ще одну перевірку на
"увімкнено/вимкнено" з таблиці) — виконуйте його з `boot()`, як
`PluginManager`, і не з `register()`.

Другий шар цієї ж пастки: помилка плагіна під час `register()`/`boot()` не
валить застосунок мовчки в продакшені —

```php
try {
    $pluginManager = $this->app->make(PluginManager::class);
    $pluginManager->autoDiscover();
    $pluginManager->registerAll();
} catch (\Throwable $e) {
    if (config('app.debug')) {
        throw $e;
    }
    report($e);
}
```

— тобто без `app.debug=true` зламаний плагін проковтується `report()`, і
про це можна дізнатись лише з логів, а не з видимого падіння адмінки.

### 7.2. Подвійний конфіг-файл (root vs package)

Див. розділ 2 повністю: редагування
`packages/nodex/nexus/src/config/nexus.php` саме по собі нічого не змінює
для ключа, який уже є в кореневому `config/nexus.php` — потрібно редагувати
обидва файли й перевіряти активне значення через
`php artisan config:show nexus.<key>`.

### 7.3. Кеш маніфесту модулів заморожує все відразу

`bootstrap/cache/nexus-modules.php` заморожує discovery одразу для
views/routes/translations/icons/commands/listeners/relations/scopes/
fieldTypes/widgets усіх модулів — не лише для однієї підсистеми. Після
будь-якої структурної зміни під `app/Nexus/**` (новий модуль, новий тип
поля, новий листенер тощо) — `php artisan nexus:module:clear`, якщо кеш
взагалі використовується в поточному оточенні.

### 7.4. `adminlte` видалено — `config('nexus.template')` де-факто підтримує лише `tailadmin`

Див. розділ 5: тема `adminlte` була мертвим артефактом (без дерева Blade-в'юх)
і видалена повністю разом із 99 МБ невикористовуваних статичних асетів.
`config('nexus.template')` формально все ще приймає будь-який рядок — інша
тема без власного `resources/views/{name}/layouts/adminpanel.blade.php`
призведе до помилок "view not found" так само, як раніше `adminlte`.
