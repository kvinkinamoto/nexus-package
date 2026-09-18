# Admin Table: дії, фільтри, lens'и, import/export, пошук, колонки

Документ описує можливості реактивної admin-таблиці Nexus (`Nodex\Nexus\Livewire\ModuleTable` + `resources/views/tailadmin/livewire/module-table.blade.php`), яка рендериться для будь-якого модуля з `#[Module(livewire: true)]`. Усі твердження нижче перевірені по коду пакета (`packages/nodex/nexus/src`), а не вигадані — де в коді чогось не знайдено, це прямо зазначено.

## 1. `#[TableAction]` і `#[TableGroupAction]` — дії над рядками

### 1.1 `#[TableAction]` — дія над одним рядком (або "головна" дія таблиці)

Клас: `Nodex\Nexus\Attributes\TableAction` (`Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE` — вішається на модель модуля багато разів).

| Параметр | Тип | За замовчуванням | Призначення |
| --- | --- | --- | --- |
| `name` | `string` | — | Ім'я дії (`edit`, `delete`, `duplicate`, довільне власне) |
| `label` | `string` | — | Ключ перекладу під `nexus::translate.{label}` |
| `icon` | `string` | — | Ключ іконки для `nexus_icon()` |
| `isConfirm` | `bool` | `false` | Чи показувати `wire:confirm` / JS-підтвердження перед виконанням |
| `isMain` | `bool` | `false` | `true` реєструє дію як таблично-рівневу "головну" дію (наприклад, "Створити") замість дії рядка |
| `isActive` | `bool` | `true` | `false` реєструє дію, але приховує/деактивує її (наприклад, вимкнути "Створити" для read-only даних) |

Ці атрибути читає `Nodex\Nexus\Services\AttributeSchemaReader::processTableAttrs()`: `isMain: true` кладеться в `$config->table->mainActions`, інакше — в `$config->table->actions`.

Рендеринг у `module-table.blade.php`:
- `mainActions` малюються кнопками з брендовим фоном у шапці таблиці (поряд з Import/Export/Settings/Columns);
- дії рядка виводяться в циклі `$tableData['actions']`. Невеликий білий список `SINGLE_RECORD_ACTIONS` (`delete`, `restore`, `deletePermanent`, `duplicate`) виконується реактивно через `wire:click="runAction('{name}', '{id}')"` → `ModuleTable::runAction()`. `edit` для модуля з `#[Module(slideOver: true)]` відкриває бічну панель (`openSlideOver()`), інакше — звичайне посилання. Будь-яка інша (кастомна) дія рендериться як `<a href="…route('nexus.module.action', …)…">` без Livewire-виклику — це full-page-запит у `NexusController::action()`; `isConfirm` при цьому підтверджується через `sendFormConfirm()` (JS у `indexLivewire.blade.php`), а не `wire:confirm`.

### 1.2 `#[TableGroupAction]` — групова (bulk) дія над вибраними рядками

Клас: `Nodex\Nexus\Attributes\TableGroupAction` (теж `TARGET_CLASS | IS_REPEATABLE`).

| Параметр | Тип | Призначення |
| --- | --- | --- |
| `name` | `string` | Ім'я дії (`deleteGroup`, `publishGroup`, довільне власне) |
| `fieldName` | `string` | Передається як `action` у `ActionGroupConfigDto`. Для 5 вбудованих імен (`deleteGroup`, `restoreGroup`, `publishGroup`, `unpublishGroup`, `duplicateGroup`) фактично ігнорується — `ModuleTable::BUILT_IN_GROUP_ACTIONS` і `BulkActionJob::BUILT_IN_GROUP_ACTIONS` виконують їх напряму через відповідні `*ActionGroupMethod`-класи. Для будь-якої іншої назви `fieldName` — це ім'я хука/методу, який шукає `CallGroupActionMethod`/`CallModuleHookAction` (before/after) |

**UI: лівий докований сайдбар, а не toolbar/dropdown.** Якщо у модуля є хоч одна активна `#[TableGroupAction]`, `module-table.blade.php` рендерить `<div x-data x-show="$wire.selected.length > 0" x-transition:… class="fixed inset-y-0 left-0 z-50 … w-72 …">` — фіксовану панель на всю висоту вʼюпорта, прикріплену до **лівого** краю екрана, яка виїжджає (`-translate-x-full` → `translate-x-0`) одразу після позначення хоч одного чекбокса рядка (`x-show` читає `$wire.selected.length` напряму, реактивний Alpine-проксі поверх Livewire-властивості). Усередині — лічильник `{{ count($selected) }} selected`, кнопка очищення вибору (`$set('selected', [])`) і список **усіх** активних `actionGroup` одразу (`wire:click="runGroupAction('{name}')"`, з `wire:confirm` коли `confirm: true`) — це не toolbar-кнопки і не dropdown-меню, тож додавання нових bulk-дій не потребує змін розмітки.

Виконання: `ModuleTable::runGroupAction()`:
1. Стріляє `Nodex\Nexus\Events\BulkActionExecuting` (Laravel-подія) і фільтр `nexus.bulk_action.executing` — `$ids` передається по референсу, слухач може частково "ветувати" вибірку (прибрати частину id) або кинути виняток, щоб скасувати всю дію.
2. Якщо `count($selected) > 50` (`ModuleTable::ASYNC_BULK_ACTION_THRESHOLD`) — диспатчиться `Nodex\Nexus\Modules\BulkAction\Jobs\BulkActionJob` (чергова джоба, обробляє вибірку чанками по 100, прогрес пишеться в `Cache` і опитується браузером через той самий ендпоінт `nexus.module.export.progress`, що й export — назва роута не специфічна для export, він просто читає значення за `cacheKey`).
3. Інакше дія виконується синхронно в тому ж запиті через `CallGroupActionMethod` (для власних `actionGroup`) або напряму через відповідний `*ActionGroupMethod` (для 5 вбудованих імен).

## 2. `#[TableFilter]` і `#[TableLens]` — фільтрація та збережені подання

### 2.1 `#[TableFilter]`

Клас: `Nodex\Nexus\Attributes\TableFilter` (`TARGET_CLASS | IS_REPEATABLE`).

| Параметр | Тип | За замовчуванням | Призначення |
| --- | --- | --- | --- |
| `name` | `string` | — | Ключ фільтра (`filter.{name}` у Livewire-стані) |
| `label` | `string` | — | Ключ перекладу |
| `type` | `string` | — | У `module-table.blade.php` реально обробляються лише `'search'` (текстове поле з `wire:model.live.debounce.400ms`) і `'select'` з непорожнім `optionsModel` (`<select>`). Інші значення з `AdminAvailableFilterEnum` (`trashed`, `is_published`, `relation`, `depth`, `date`) у цій вʼюсі власного UI-блоку не мають — власного `@elseif` для них у `module-table.blade.php` не знайдено |
| `optionsModel` | `?string` | `null` | Тільки для `type: 'select'` — клас Eloquent-моделі, чиї рядки наповнюють `<option>`, запитується заново на кожен рендер |
| `optionsValue` | `string` | `'id'` | Колонка — значення `<option>` (і значення, яке йде у `FilterHandler`) |
| `optionsLabel` | `string` | `'name'` | Колонка — підпис `<option>`, за нею ж іде `orderBy` |

Обробка: `AttributeSchemaReader::processTableAttrs()` кладе фільтр у `$config->table->filters` через `TableConfigDto::filter()`. Застосування значення до запиту — `AddFilterActionMethod::handle()` (детальніше в розділі 4 — саме тут прихована логіка "generic search fallback").

### 2.2 `#[TableLens]`

Клас: `Nodex\Nexus\Attributes\TableLens` (`TARGET_CLASS | IS_REPEATABLE`). За власним докблоком атрибута — це "Nova-style Lens без кастомного query" — перевикористовує той самий формат умов, що й `UniversalFilterBuilder`.

| Параметр | Тип | За замовчуванням | Призначення |
| --- | --- | --- | --- |
| `name` | `string` | — | Унікальний ключ, читається з `?lens=` (або з Livewire-стану `lens`) |
| `label` | `string` | — | Ключ перекладу під `{module}::translate`, показується на вкладці |
| `conditions` | `array` | `[]` | Масив умов у форматі `UniversalFilterBuilder::apply()`: `['column' => …, 'operator' => …, 'value' => …, 'logic' => 'AND'\|'OR']` |
| `icon` | `?string` | `null` | Іконка вкладки (`nexus_icon()`) |
| `columns` | `?array` | `null` | Імена колонок, які показувати, поки цей lens активний — перекриває звичайний default/user-saved набір видимих колонок |
| `sort` | `?string` | `null` | Колонка сортування за замовчуванням, поки lens активний (явний `?sort=` користувача все одно має пріоритет) |

Приклад із докблоку атрибута:

```php
#[TableLens(name: 'published', label: 'lens_published', conditions: [
    ['column' => 'is_published', 'operator' => '=', 'value' => 1],
])]
#[TableLens(name: 'out_of_stock', label: 'lens_out_of_stock', conditions: [
    ['column' => 'stock', 'operator' => '<=', 'value' => 0],
])]
```

Рендеринг: `module-table.blade.php` малює lens-и як вкладки над таблицею (`wire:click="selectLens('{name}')"`), кожна з бейджем-лічильником (`$tableData['lensCounts'][$lensName]`) — це окремий `COUNT()`-запит на кожен lens, незалежний від активних фільтрів/пошуку. `TableBuilder::build()`: якщо активний lens знайдено, його `conditions` мерджаться з довільними `dyn`-фільтрами (Universal Filters панель), а `columns`/`sort` lens'а перекривають звичайну логіку видимих колонок і дефолтного сортування.

## 3. `#[TableImport]` та модуль Export — CSV import/export

### 3.1 Export — увімкнений за замовчуванням, без атрибута

У пакеті немає атрибута `#[TableExport]` — папка `Attributes/Table*.php` містить лише `TableAction`, `TableGroupAction`, `TableFilter`, `TableLens`, `TableImport`. Export вмикається автоматично: `DefaultModuleConfigurationDto::__construct()` завжди реєструє `exports: ['export' => new ExportConfigDto('export', 'Export')]` — тобто кожен модуль отримує кнопку "Export" одразу, якщо явно не очистити `$table->exports`.

Потік:
1. `module-table.blade.php` — кнопка `wire:click="exportTable"` (видима, коли `$module->config->table->exports` не пустий).
2. `ModuleTable::exportTable()` диспатчить `Nodex\Nexus\Modules\Export\Jobs\MasterExportJob` із поточним станом `filter`/`sort`/`lens` — тобто експортується таблиця "як зараз відфільтрована", а не незалежний повний зліпок.
3. `MasterExportJob` перевикористовує `TableBuilder::build(..., sql: true)` — той самий запит, що й для звичайного рендеру таблиці — і чанками (`chunkSize`, дефолт 5000) пише CSV у `php://temp`, з прогресом у `Cache` (`{progress, processed, total, status}`).
4. На кожен рядок стріляє `Nodex\Nexus\Events\ExportRowBuilding` (`$row` — впорядкований масив CSV-клітинок, по референсу) і фільтр `nexus.export.row` — дозволяє модулю/плагіну переформатувати або редагувати значення без окремого export-пайплайна.
5. Готовий файл кладеться на `Storage::disk('local')` в `exports/{module}_{Ymd_His}_{6 символів cacheKey}.csv`.
6. Браузер опитує прогрес через `nexus.module.export.progress?cacheKey=…` (JS `pollNexusProgress()` у `indexLivewire.blade.php`) і при `status === 'completed'` переходить на `nexus.module.export.download`.

### 3.2 `#[TableImport]` — опційний, бо пише дані

Клас: `Nodex\Nexus\Attributes\TableImport` (`TARGET_CLASS`, **не** repeatable). Із докблоку: на відміну від `TableAction`/`TableGroupAction`, import не має авто-реєстрованого дефолту — модуль повинен явно оголосити, що хоче цю можливість, бо вона записує дані.

| Параметр | Тип | За замовчуванням | Призначення |
| --- | --- | --- | --- |
| `name` | `string` | — | Ім'я імпорту |
| `label` | `?string` | `null` | Підпис кнопки |
| `icon` | `string` | `''` | Іконка |
| `confirm` | `bool` | `true` | Чи підтверджувати запуск |
| `isActive` | `bool` | `true` | Вмикає/вимикає |

Приклад із докблоку атрибута:

```php
#[TableImport(name: 'import', label: 'Import')]
```

Потік (`Nodex\Nexus\Services\Actions\Admin\ImportActionMethod`, **синхронний**, без черги — на відміну від export):
1. Кнопка Import (файловий `<input type="file" accept=".csv,text/csv">`) відправляє `fetch()`-запит на `{admin_prefix}/{module}/import` (делегований `change`-обробник у `indexLivewire.blade.php`, щоб пережити Livewire-морфінг DOM).
2. Заголовок CSV зіставляється з колонками модуля по `label` **або** по `name` (`$columnsByLabel`/`$columnsByName`, побудовані з `$moduleConfig->table->columns`).
3. Кожен рядок: якщо є колонка `id` — робиться `find()`+`update()`, інакше — `create()`. На кожен рядок стріляє `Nodex\Nexus\Events\ImportRowBuilding` (`$data` по референсу) і фільтр `nexus.import.row` — **до** перетину з `$fillable`, тож плагін може трансформувати значення, але не може підсунути поле, яке модель і так не дозволяє масово присвоювати.
4. "Погані" рядки (виняток при `create`/`update`) пропускаються (`skipped++`), а не обривають увесь файл.
5. Відповідь — JSON `{created, updated, skipped}`, який JS показує у `#nexusImportStatus-{module}` і після цього робить `$refresh` компонента Livewire.

## 4. Generic search fallback

У пакеті насправді **два незалежні** механізми пошуку — варто їх не плутати.

### 4.1 Пошук усередині таблиці модуля (`#[TableFilter(type: 'search')]`)

Значення текстового поля пошуку йде як `filter.search` і обробляється `Nodex\Nexus\Services\Actions\Admin\AddFilterActionMethod::handle()`:

```php
$filterModuleHandler = ModuleManager::getClassFromModule($module->name.'\\Filters\\ModuleFilterHandler')
    ?? new FilterHandler;
```

Якщо модуль **не має власного** `{Module}/Filters/ModuleFilterHandler.php`, використовується базовий `Nodex\Nexus\Filters\FilterHandler`. Його метод `filter()` реалізує два "зарезервовані" імені фільтра генерично, для будь-якого модуля безкоштовно:

- **`search`** — `LOWER(column) LIKE '%term%'` (через `orWhereRaw`) по **всіх фізичних колонках таблиці моделі** (`SchemaColumnsCache::get($table)`), а не тільки по колонках, позначених `searchable` — тобто дефолтний пошук у таблиці модуля "сліпо" LIKE-ить кожну фізичну колонку БД, ігноруючи, чи є у неї `#[Column(searchable:)]` взагалі;
- **`trashed`** — якщо модель використовує `SoftDeletes`, перемикає `withTrashed()`/`onlyTrashed()`/`withoutTrashed()`.

Команда `php artisan nexus:make:filter {module}` (`Nodex\Nexus\commands\MakeFilterCommand`) генерує саме такий `Filters/ModuleFilterHandler.php` (наслідує `FilterHandler`, перевизначає `filter()`) — авто-резолвиться по імені, реєструвати вручну не треба; команда одразу нагадує, що будь-який доданий у ньому фільтр треба продублювати в `TableConfigDto->filters` (тобто оголосити `#[TableFilter]`/`$table->filter()`), інакше в адмінці не зʼявиться UI для нього.

### 4.2 Наскрізний (cross-module) пошук — `GlobalSearchService`

Це окремий сервіс (`Nodex\Nexus\Services\GlobalSearchService`), який шукає **одразу по всіх увімкнених модулях**, на відміну від "сліпого" LIKE в 4.1: він бере лише колонки, явно позначені `#[Column(searchable: true)]` (`ColumnConfigDto::$searchable`), щоб ніколи не показати внутрішні/чутливі колонки (хеші паролів, токени, FK). Для кожного модуля, де є хоч одна `searchable`-колонка й дозволений `index`-permission, робиться `orWhere(column, 'LIKE', "%term%")` по цих колонках, ліміт `$limitPerModule` (дефолт 5) рядків, підпис результату береться з першої `searchable`-колонки (`array_key_first`), посилання веде на `edit` через `route('nexus.module.action', …)`. Наприкінці стріляє `Nodex\Nexus\Events\GlobalSearchCompleted` (`$results` по референсу) і фільтр `nexus.search.results` — дозволяє плагіну додати свою групу результатів (зовнішнє API, не-модульне джерело) або переранжувати/обрізати вже зібране.

## 5. Per-user column visibility (picker колонок)

Зберігання: таблиця `nexus_user_table_preferences` (міграція `2025_01_01_000000_create_nexus_tables.php`):

```php
Schema::create('nexus_user_table_preferences', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
    $table->string('module');
    $table->json('visible_columns')->nullable();
    $table->json('filters')->nullable();
    $table->timestamps();
    $table->unique(['user_id', 'module']);
});
```

Тобто на кожну пару `(user_id, module)` — один рядок; `visible_columns` — JSON-масив імен колонок. (Стовпець `filters` у тій же таблиці існує для збережених "Universal Filters" — `NexusController::saveDynamicFilters()` — до пікера колонок прямого відношення не має.)

**Читання** — `TableBuilder::build()`: якщо переданий `$userId`, читається рядок `nexus_user_table_preferences` за `(user_id, module)`; якщо він є і `visible_columns` не порожній — видимі колонки фільтруються саме за цим списком; інакше — дефолт: усі колонки з `ColumnConfigDto::$tableDefault !== false`. Якщо активний lens з власним `columns`, він перекриває обидва варіанти (розділ 2.2).

**Запис** — два незалежні шляхи, які пишуть в один і той же рядок тим самим чином:
- Livewire-шлях (реактивний, актуальний для `#[Module(livewire: true)]`): дропдаун "Columns" у `module-table.blade.php` (кнопка з іконкою `bx-columns`, видима тільки якщо колонок > 1) — чекбокс на кожну колонку з `wire:click="toggleColumnVisibility('{name}')"` → `ModuleTable::toggleColumnVisibility()`, який бере поточний видимий набір із вже порахованих `$tableData['columns']`, додає/прибирає колонку і робить `DB::table('nexus_user_table_preferences')->updateOrInsert(...)`. Порожній результуючий набір (спроба прибрати останню видиму колонку) ігнорується — метод повертається, нічого не змінюючи.
- HTTP-шлях: `NexusController::saveTableColumns()` — той самий `updateOrInsert`, лишається для не-Livewire викликів (легасі-роут `PUT/POST {module}/columns` чи подібний — сам роут у цьому документі не звірявся).

## 6. Приклад: комбінація атрибутів на гіпотетичному модулі

Нижче — приклад у стилі реального `packages/nodex/nexus/src/Modules/Role/Models/Role.php` (структура `#[Module]` + `#[TableAction]`/`#[TableGroupAction]` + `#[Column]`/`#[Field]` підтверджена по цьому файлу), що комбінує пошук, select-фільтр, два lens'и, bulk-дію, import і власний `ModuleFilterHandler`:

```php
namespace App\Nexus\Modules\ShopProduct\Models;

use Nodex\Nexus\Attributes\Column;
use Nodex\Nexus\Attributes\Field;
use Nodex\Nexus\Attributes\Module;
use Nodex\Nexus\Attributes\TableAction;
use Nodex\Nexus\Attributes\TableFilter;
use Nodex\Nexus\Attributes\TableGroupAction;
use Nodex\Nexus\Attributes\TableImport;
use Nodex\Nexus\Attributes\TableLens;
use Nodex\Nexus\Concerns\HasAttributeSchemaProperties;

#[Module(name: 'shopProduct', label: 'Товари', icon: 'box', group: 'Shop', livewire: true)]
#[TableFilter(name: 'search', label: 'search', type: 'search')]
#[TableFilter(
    name: 'category_id',
    label: 'category',
    type: 'select',
    optionsModel: \App\Nexus\Modules\ShopCategory\Models\ShopCategory::class,
    optionsValue: 'id',
    optionsLabel: 'name',
)]
#[TableLens(name: 'published', label: 'lens_published', conditions: [
    ['column' => 'is_published', 'operator' => '=', 'value' => 1],
])]
#[TableLens(name: 'out_of_stock', label: 'lens_out_of_stock', conditions: [
    ['column' => 'stock', 'operator' => '<=', 'value' => 0],
])]
#[TableAction(name: 'edit', label: 'Edit', icon: 'edit')]
#[TableAction(name: 'delete', label: 'Delete', icon: 'delete', isConfirm: true)]
#[TableGroupAction(name: 'deleteGroup', fieldName: 'delete')]
#[TableGroupAction(name: 'publishGroup', fieldName: 'is_published')]
#[TableImport(name: 'import', label: 'Import')]
class ShopProduct extends \Illuminate\Database\Eloquent\Model
{
    use HasAttributeSchemaProperties;

    #[Column(label: 'Name', sortable: true, searchable: true)]
    #[Field(type: 'string', section: 'information', label: 'Name')]
    protected $name;

    #[Column(label: 'Price', sortable: true)]
    #[Field(type: 'number', section: 'information', label: 'Price')]
    protected $price;

    #[Column(label: 'Stock', sortable: true)]
    #[Field(type: 'number', section: 'information', label: 'Stock')]
    protected $stock;
}
```

Разом з таким модулем варто згенерувати власний обробник пошуку (інакше пошук працюватиме через "сліпий" LIKE по всіх фізичних колонках, розділ 4.1):

```
php artisan nexus:make:filter ShopProduct
```

— це створить `App\Nexus\Modules\ShopProduct\Filters\ModuleFilterHandler`, де можна звузити `search` до конкретних колонок (наприклад, лише `name`/`sku`) замість LIKE по всій таблиці.

`publishGroup` тут — вбудоване ім'я (`ModuleTable::BUILT_IN_GROUP_ACTIONS`), тож `fieldName: 'is_published'` формально не використовується рушієм (виконання йде напряму через `PublishActionGroupMethod`), але задокументувати його варто так само, як роблять вбудовані значення за замовчуванням у `DefaultModuleConfigurationDto`.
