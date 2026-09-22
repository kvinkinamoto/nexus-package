# Плагіни та хуки (Plugin & Hooks system)

Цей документ описує механізм розширення Nexus, який **не** передбачає власної таблиці в БД:
плагіни (`app/Nexus/Plugins/**`). Якщо потрібен новий контент-тип зі своєю таблицею — дивіться
документацію по модулях, цей файл — суто про розширення *чужої* поведінки.

## Плагін проти модуля

- **Модуль** (`app/Nexus/Modules/{Name}`) володіє своєю Eloquent-моделлю, міграцією, admin CRUD.
- **Плагін** (`app/Nexus/Plugins/{Name}/{Name}Plugin.php`) нічим не володіє. Він або мутує
  конфігурацію *чужого* модуля (додає поле/колонку/фільтр, змінює правила валідації, підмінює
  пункт меню), або виконує наскрізну (cross-cutting) логіку застосунку рівня — реєструє маршрут,
  тип блоку, аліас field-type, слухає життєвий цикл сутностей будь-якого модуля.

Плагін — це звичайний PHP-клас, який Nexus сам знаходить на кожному запиті
(`PluginManager::autoDiscover()`) — реєструвати його вручну у `ServiceProvider` не потрібно.

## Швидкий старт

```bash
php artisan nexus:make:plugin ReviewAdmin --module=Product
```

`--module` за замовчуванням `User` і визначає атрибут `#[TargetModule]`. Команда створює
`app/Nexus/Plugins/ReviewAdmin/ReviewAdminPlugin.php` зі скелетом:

```php
#[TargetModule('Product')]
class ReviewAdminPlugin
{
    public function register(): void {}
    public function boot(): void {}
    public function handle(object $configuration): void {}

    #[Filter(hook: 'nexus.validation.rules', priority: 10)]
    public function extendValidationRules(array $rules, object $moduleConfig, string $action): array
    {
        return $rules;
    }

    #[Action(hook: 'nexus.field_types.register', priority: 10)]
    public function registerFieldTypes(FieldTypeRegistry $registry): void {}
}
```

Видаліть методи, які вам не потрібні — `PluginManager` викликає `register()`/`boot()`/`handle()`
лише якщо вони існують.

## `#[TargetModule]` — обов'язковий якір

```php
#[Attribute(Attribute::TARGET_CLASS)]
class TargetModule
{
    public function __construct(public string $name) {}
}
```

Атрибут класу, що вказує, конфігурацію якого модуля мутує плагін. Він робить дві речі:

1. Реєструє клас у `PluginManager`, щоб `handle(object $configuration)` викликався щоразу, коли
   збирається конфігурація вказаного модуля (`ModuleManager::resolveModuleConfig()` →
   `PluginManager::apply()`). `$configuration` — **свіжа глибока копія** DTO схеми модуля на
   кожен виклик, її можна вільно мутувати — вона ніколи не "протікає" між запитами чи модулями.
2. Тільки завдяки цьому атрибуту `PluginManager::registerAll()`/`bootAll()` взагалі бере клас до
   уваги — `register()`/`boot()` викликаються лише для класів, зареєстрованих через
   `#[TargetModule]`.

> **Важливо.** `#[Filter]`/`#[Action]`-методи класу підключаються до `HookManager` **незалежно**
> від наявності `#[TargetModule]` — рефлексія методів відбувається завжди. Але якщо у плагіна
> немає `#[TargetModule]`, його `register()`/`boot()` просто ніколи не викличуться. Тому, навіть
> якщо єдина мета плагіна — зареєструвати маршрут чи тип блоку в `boot()` і жодної конфігурації
> модуля він не чіпає, все одно лишайте `#[TargetModule('User')]` як нейтральний якір (`User` —
> завжди встановлений стартовий модуль, тому й дефолт команди `nexus:make:plugin` саме такий).
> Це видно на прикладі реальних плагінів `GraphQLPlugin` і `BlockTypesPlugin` — обидва несуть
> `#[TargetModule('User')]` з порожнім `handle()`, а вся робота — в `boot()`.

## `#[Filter]` / `#[Action]` і хелпери `nexus_filter()` / `nexus_action()`

Це именований, пріоритетно впорядкований механізм розширення в стилі WordPress
(`apply_filters()`/`do_action()`) — будь-який модуль чи плагін може підключитись до точки
розширення, не редагуючи код, що її оголошує.

- **Filter** — трансформує значення і **повинен повернути** (можливо, змінене) значення.
- **Action** — виконує побічний ефект, повернене значення ігнорується.

```php
#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Filter
{
    public function __construct(
        public readonly string $hook,
        public readonly int $priority = 10,
    ) {}
}
```

`#[Action]` має ідентичну сигнатуру. Обидва атрибути можна вішати на один метод повторно (для
кількох хуків), метод повинен бути **публічним** — `PluginManager::discoverClass()` рефлексує
лише `ReflectionMethod::IS_PUBLIC`. Менший `priority` виконується раніше (дефолт — 10, як у
WordPress).

Виклик хука в коді модуля чи пакета — через глобальні хелпери:

```php
nexus_filter(string $hook, mixed $value, mixed ...$args): mixed;   // повертає трансформоване значення
nexus_action(string $hook, mixed ...$args): void;
```

### Приклад: реєстрація й виклик

```php
// app/Nexus/Plugins/Example/ExamplePlugin.php
#[TargetModule('Demo')]
class ExamplePlugin
{
    #[Filter(hook: 'nexus.validation.rules', priority: 10)]
    public function extendValidationRules(array $rules, object $moduleConfig, string $action): array
    {
        if ($moduleConfig->name === 'demo' && isset($rules['title'])) {
            $titleRules = is_string($rules['title']) ? explode('|', $rules['title']) : $rules['title'];
            $titleRules[] = 'max:100';
            $rules['title'] = $titleRules;
        }

        return $rules;
    }

    #[Action(hook: 'nexus.field_types.register', priority: 10)]
    public function registerFieldTypes(FieldTypeRegistry $registry): void
    {
        $registry->alias('examplePluginAlias', 'string');
    }
}
```

Виклик (десь у пакеті — саме тут хук насправді викликається для будь-якого модуля):

```php
$rules = nexus_filter('nexus.validation.rules', $rules, $moduleConfig, $action);
nexus_action('nexus.field_types.register', app(FieldTypeRegistry::class));
```

Зверніть увагу — хук **не** обмежений одним модулем: усі зареєстровані слухачі
`nexus.validation.rules` викликаються для кожного модуля. Якщо потрібна поведінка лише для
конкретного модуля — перевіряйте це всередині методу (`if ($moduleConfig->name !== 'demo') return $rules;`),
як у прикладі вище.

`[ClassName, 'method']`-колбек резолвиться через контейнер **лінькво**, у момент виклику хука —
клас плагіна не інстанціюється, якщо жоден з його хуків фактично не спрацював.

## `#[AttachField]` / `#[AttachColumn]` / `#[AttachFilter]` — додавання поля/колонки/фільтра на чужий модуль

Декларативний цукор над `handle()` для найпоширенішого сценарію: спеціалізований модуль (напр.
`Review`) додає одне admin-поле/колонку/фільтр на базовий модуль (напр. `Product`), яким не
володіє — без того, щоб файл `Product` хоч колись згадував `Review`.

Атрибути ставляться на публічний метод-маркер класу, що також несе `#[TargetModule]`. **Тіло
методу ніколи не викликається** — читаються лише атрибути (як і в `#[Filter]`/`#[Action]`):

```php
#[TargetModule('Product')]
class ReviewAdminPlugin
{
    #[AttachField(name: 'reviews', type: 'relationManager', section: 'relations', label: 'review::translate.reviews', permission: 'review_view')]
    #[Relation(type: 'hasMany', show: 'title', relatedModule: 'review')]
    public function reviewsField(): void {}

    #[AttachColumn(name: 'reviews_count', label: 'review::translate.reviews', permission: 'review_view')]
    public function reviewsCountColumn(): void {}

    #[AttachFilter(name: 'has_reviews', label: 'review::translate.has_reviews', type: 'search')]
    public function reviewsFilter(): void {}
}
```

Параметри:

| Атрибут | Параметри конструктора |
| --- | --- |
| `AttachField` | `name`, `type`, `section = 'default'`, `label = null`, `isRequired = false`, `order = 0`, `apiExpose = false`, `permission = null` |
| `AttachColumn` | `name`, `label = null`, `sortable = false`, `tableDefault = true`, `order = 0`, `permission = null` |
| `AttachFilter` | `name`, `label = null`, `type = 'search'`, `permission = null` |

Важливі нюанси:

- **`#[AttachField]` + `#[Relation]` лише робить поле видимим у формі `Product`** — сам Eloquent-
  зв'язок повинен існувати окремо, оголошений з боку `Review` через `#[AttachRelation]` (див.
  нижче). Обидві половини потрібні, щоб `relationManager`-поле реально працювало — саме по собі
  `AttachField` не змусить `$product->reviews()` резолвитись.
- **`label` має бути у формі `'ownNamespace::translate.key'`**, а не голим рядком. Партіали
  `_label.blade.php`/`module-table.blade.php` резолвлять голий label проти лейбл-файлу
  **цільового** модуля (`Product`, який ви не можете чіпати) — форма з `'::'` обходить це й бере
  переклад з довільного простору імен, тож реальний переклад кладіть у `resources/lang/{locale}/translate.php`
  свого модуля (`Review`).
- **`permission`, якщо заданий, незалежно гейтить приєднання для кожного глядача** окремо від
  того, яке право вже вимагає власна дія edit/view `Product`. Запис, який поточний глядач не може
  бачити, взагалі не потрапляє в конфігурацію (не рендериться прихованим) — саме це не дає даним
  платного/опційного модуля протекти до кожного редактора безкоштовного базового модуля. Залиште
  `null`, щоб успадкувати гейт цільового модуля (дефолтна, зворотно сумісна поведінка).
- `#[AttachField(apiExpose: true)]` віддзеркалює `#[Field(apiExpose:)]` — виставляє приєднане
  поле через REST/GraphQL так само, як і власне поле модуля.
- Значення `#[AttachColumn]`/`#[AttachFilter]` все одно має резолвитись на цільовій моделі як
  звичайна колонка/фільтр — ці атрибути лише роблять запис видимим, даних вони не створюють.
  Обчислювана колонка (напр. `_count`) потребує парного `#[AttachScope]` з `withCount(...)`.
- Усе трьох атрибутів обробляє `PluginManager` разом із `#[Filter]`/`#[Action]`. Вимкнення
  плагіна (або відсутність модуля `Review`) прибирає приєднання на наступному запиті без падінь —
  fail-quiet, ніколи не 500.

## `#[AttachRelation]` / `#[AttachScope]` — розв'язка зв'язків і scope між модулями

Ці атрибути стоять не на плагіні, а на публічних **статичних** методах у папці `Relations/`
модуля (наприклад `app/Nexus/Modules/Review/Relations/ProductRelations.php`) і працюють поверх
стандартних механізмів Eloquent — `Model::resolveRelationUsing()` та `Model::addGlobalScope()`
відповідно, нічого специфічного для Nexus тут немає.

```php
#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class AttachRelation
{
    public function __construct(
        public readonly string $model,
        public readonly string $name,
    ) {}
}
```

```php
#[AttachRelation(model: \App\Nexus\Modules\ShopProduct\Models\ShopProduct::class, name: 'reviews')]
public static function reviews(ShopProduct $product): HasMany
{
    return $product->hasMany(Review::class, 'product_id');
}
```

```php
#[AttachScope(model: \App\Nexus\Modules\ShopProduct\Models\ShopProduct::class, name: 'published')]
public static function published(): \Closure
{
    return fn ($query) => $query->where('is_published', true);
}
```

`AttachScope::$name` за замовчуванням дорівнює імені методу, якщо не заданий явно. Напрям
залежності тут навмисно перевернутий: спеціалізований модуль (`Review`) знає про базовий
(`ShopProduct`), а не навпаки — `ShopProduct` ніколи не дізнається, що `Review` взагалі існує.

`#[AttachRelation]` — це саме той шматок, що потрібен, щоб `relationManager`-поле з
`#[AttachField]` вище реально резолвилось: `#[AttachField]`+`#[Relation]` лише показує поле у
формі, `#[AttachRelation]` — робить `$product->reviews` робочим зв'язком.

## Каталог подій (Events) і їхні пари з хуками

Майже кожна точка розширення в пакеті спрацьовує **двічі в одному й тому самому місці**: спершу
справжня Laravel-подія, одразу за нею — відповідний `nexus_filter()`/`nexus_action()` з
ідентичними даними. Це навмисне дублювання, а не заміна одне одного: власний
`Listeners/`-клас модуля (не потребує ані плагіна, ані `#[TargetModule]`) — природний вибір для
реакції конкретного модуля, а `#[Filter]`/`#[Action]` плагіна — для перевикористовуваної,
міжмодульної, незалежно вимикної логіки.

Подія з назвою на `*ing`/`*Building`/`*Preparing`/`*Resolving` мутує властивість **за
посиланням** (`&$data`, `&$rules`, `&$result`, ...) — пишіть у цю властивість напряму, нічого не
`return`-те з обробника. Кидання винятку з такої події перериває операцію звичайним виключенням
(окремого прапорця "скасувати" в пакеті немає). Подія в минулому часі (`EntityCreated`,
`ModuleInstalled`, ...) — це чисте сповіщення про факт, мутувати нічого.

### Життєвий цикл сутності

`$moduleConfig` — DTO схеми цільового модуля (`DefaultModuleConfigurationDto`).

| Момент | Клас події | Хук (тип) | Дані |
| --- | --- | --- | --- |
| Перед створенням (в т.ч. дублювання) | `EntityCreating` | `nexus.entity.creating` (filter) | `Model $model, array &$data, $moduleConfig` |
| Після створення | `EntityCreated` | `nexus.entity.created` (action) | `Model $model, $moduleConfig` |
| Перед оновленням | `EntityUpdating` | `nexus.entity.updating` (filter) | `Model $model, array &$data, array $oldData, $moduleConfig` |
| Після оновлення | `EntityUpdated` | `nexus.entity.updated` (action) | `Model $model, $moduleConfig` |
| Перед видаленням | `EntityDeleting` | `nexus.entity.deleting` (action) | `Model $model, $moduleConfig` |
| Після видалення | `EntityDeleted` | `nexus.entity.deleted` (action) | `Model $model, $moduleConfig` |
| Перед відновленням | `EntityRestoring` | `nexus.entity.restoring` (action) | `Model $model, $moduleConfig` |
| Після відновлення | `EntityRestored` | `nexus.entity.restored` (action) | `Model $model, $moduleConfig` |
| Будь-яка table/group action завершена (delete/restore/duplicate/кастомна) | `ModuleActionExecuted` | `nexus.module.action_executed` (action) | `string $moduleName, string $actionName, ?string $id, ?array $ids` — read-only дії (index/edit/create/view) не входять |
| Масова дія над вибіркою от-от виконається | `BulkActionExecuting` | `nexus.bulk_action.executing` (filter) | `string $moduleName, string $actionName, array &$ids` — можна прибрати частину id (часткове вето) |

### Побудова форми/таблиці модуля в адмінці

Загальний, імперативний "родич" `#[AttachField]`/`#[AttachColumn]` — дозволяє додати поле/
колонку/вкладку, не чіпаючи файл модуля.

| Момент | Клас події | Хук (тип) | Дані |
| --- | --- | --- | --- |
| Конфігурація форми зібрана, перед рендером | `AdminFormBuilding` | `nexus.form.building` (action) | `moduleName, config, ?Model $model, ?array $liveData` — мутуйте `$event->config` напряму |
| Поля форми фіналізовані | `FormFieldsPrepared` | `nexus.form.fields_prepared` (filter) | `moduleName, array &$fields` |
| Конфігурація таблиці зібрана, перед рендером | `AdminTableBuilding` | `nexus.table.building` (action) | `moduleName, config` — мутуйте `$event->config` (колонки/фільтри/дії) |
| Рядки таблиці отримані | `TableDataPrepared` | `nexus.table.data_prepared` (filter) | `moduleName, array &$data` |

### Валідація

| Момент | Клас події | Хук (тип) | Дані |
| --- | --- | --- | --- |
| `FormRequest` от-от валідується | `PreparingForValidation` | `nexus.validation.preparing` (action) | `FormRequest $request` |
| Правила зібрані (спрацьовує і для власного рукописного `AdminStoreRequest`/`AdminUpdateRequest` модуля — через `NexusFormRequest::withValidator()`) | `GatheringValidationRules` | `nexus.validation.rules` (filter) | `$moduleConfig, array &$rules, string $action` |

### API, імпорт/експорт

| Момент | Клас події | Хук (тип) | Дані |
| --- | --- | --- | --- |
| Загальний `NexusResource` серіалізує модель (пропускається повністю, якщо у модуля є власний `{Model}Resource`) | `ApiResourceBuilding` | `nexus.api.resource` (filter) | `Model $resource, array &$data` |
| Кожен рядок CSV-експорту | `ExportRowBuilding` | `nexus.export.row` (filter) | `Model $model, array &$row, string $moduleName` |
| Кожен рядок CSV-імпорту, перед mass-assignment (у будь-якому разі фільтрується по fillable) | `ImportRowBuilding` | `nexus.import.row` (filter) | `array &$data, $moduleConfig` |

### Права, меню, дашборд

| Момент | Клас події | Хук (тип) | Дані |
| --- | --- | --- | --- |
| Рішення про право щойно обчислене | `PermissionChecking` | `nexus.permission.check` (filter) | `string $action, ?Module $module, string $place, bool &$result` |
| Бокове меню зібране | `SidebarMenuBuilding` | `nexus.menu.sidebar` (filter) | `array &$menu` — масив `MenuConfigDto`, можна додати пункт, не прив'язаний до жодного модуля |
| Layout дашборду вирішено (каскад own-row → is_default-row → config вже застосовано) | `DashboardLayoutResolving` | `nexus.dashboard.layout` (filter) | `?Authenticatable $user, array &$layout` |

### Медіа, виявлення/життєвий цикл модулів, пошук, сповіщення

| Момент | Клас події | Хук (тип) | Дані |
| --- | --- | --- | --- |
| Перед приєднанням медіа-файлу (кидання винятку відхиляє завантаження) | `MediaAttaching` | `nexus.media.attaching` (action) | `Model $model, UploadedFile $file, string $collection` |
| Після приєднання дійсно нового файлу (не sha256-дедуп) | `MediaAttached` | `nexus.media.attached` (action) | `Model $model, MediaItemDto $item, string $collection` |
| Список модулів зібрано, після файлової сканки Modules/UserModules | `ModuleDiscoveryCompleted` | `nexus.module.discovery` (filter) | `Collection &$modules` — додати модуль без реальної директорії, або приховати наявний |
| Модуль щойно встановлено / видалено | `ModuleInstalled` / `ModuleUninstalled` | `nexus.module.installed` / `nexus.module.uninstalled` (action) | `string $moduleName` |
| Результати глобального пошуку зібрані | `GlobalSearchCompleted` | `nexus.search.results` (filter) | `array &$results, string $term` — додати групу результатів поза модулями |
| Payload дзвіночка сповіщень в адмінці зібраний | `NotificationDataBuilding` | `nexus.notification.data` (filter) | `Notification $notification, $notifiable, array &$data` |

### Типи полів, віджети (boot-time / вивід)

| Момент | Клас події | Хук (тип) | Дані |
| --- | --- | --- | --- |
| Реєстрація типів полів на boot (третя й остання точка реєстрації) | `FieldTypesRegistering` | `nexus.field_types.register` (action) | `FieldTypeRegistry $registry` |
| `data` чи рендерений `html` віджета вирішено, **до** кешування `WidgetOutputCache` | `WidgetOutputResolving` | `widget.data.{key}` / `widget.html.{key}` (filter) | `string $widgetKey, string $kind, mixed &$output, WidgetContext $context` |

Кожен клас події з таблиць вище лежить у `packages/nodex/nexus/src/Events/` з повним докблоком
(обґрунтування + готовий приклад слухача) — перед тим, як здогадуватись про форму payload,
відкрийте сам клас. `ModuleEvent` — старіша, нетипізована подія, залишена для зворотної
сумісності; для нового коду обирайте конкретну типізовану подію з таблиць вище.

## Скафолдинг команди

```bash
php artisan nexus:make:plugin {name} --module={module}
```

Створює `app/Nexus/Plugins/{name}/{name}Plugin.php` зі скелетом з `#[TargetModule]`,
`register()`/`boot()`/`handle()` і прикладами `#[Filter]`/`#[Action]`. Ніякої додаткової
реєстрації не потрібно — плагін підхопиться автоматично на наступному запиті.

```bash
php artisan nexus:make:filter {module}
```

Це **окрема** команда — вона не пов'язана з атрибутом `#[Filter]`/хуками вище, а створює
`app/Nexus/Modules/{module}/Filters/ModuleFilterHandler.php`, клас, що перевизначає
`FilterHandler::filter()` для *таблиці адмінки* цього модуля. Він потрібен, коли
`#[AttachFilter]` (чи власний `#[TableFilter]` модуля) оголошує `type`, відмінний від
дефолтного `'search'` — `'search'` уже реалізований базовим `FilterHandler` (LIKE по всіх
колонках таблиці) і додаткового коду не потребує, а кастомний `type` треба обробити тут:

```php
class ModuleFilterHandler extends FilterHandler
{
    public function filter(Builder $query, string $filterName, string $value): Builder
    {
        return match ($filterName) {
            // 'category' => $query->where('category_id', $value),
            default => parent::filter($query, $filterName, $value),
        };
    }
}
```

Клас резолвиться автоматично за іменем модуля — реєструвати вручну не потрібно, але кожен
кастомний фільтр однаково має бути оголошений у `TableConfigDto->filters` модуля (через
`#[TableFilter]` або `#[AttachFilter]`), інакше він просто не з'явиться в UI списку.

## Виявлення плагінів і момент завантаження (boot timing)

`PluginManager::autoDiscover()` сканує `app_path('Nexus/Plugins')` на глибину одного рівня
(`{Plugin}/*.php`), а також окремі `.php`-файли безпосередньо в `Nexus/Plugins/` (для простих
однофайлових плагінів). Вимкнути плагін без видалення файлу:

```php
// config/nexus.php
'plugins' => [
    'disabled' => [
        \App\Nexus\Plugins\Foo\FooPlugin::class,
    ],
],
```

Ключа `plugins` немає в дефолтному конфіг-файлі пакета "з коробки" — за потреби додайте масив
самі; `config('nexus.plugins.disabled', [])` однаково впаде на порожній масив, якщо ключа немає.

> **Важливо: `boot()`, а не `register()`.** Сам пакет викликає `PluginManager::autoDiscover()`
> та `registerAll()` з `NexusServiceProvider::boot()`, **не** з `register()` — і на це є
> конкретна причина: `PluginManager::isPluginEnabled()` (перемикач "увімк/вимк" плагіна з
> адмінки, таблиця `nexus_plugins`) читає з БД, а резолвер з'єднання Eloquent ще не готовий під
> час `register()` — `register()` кожного провайдера в Laravel виконується раніше за `boot()`
> будь-якого провайдера, включно з провайдером самого Eloquent. `bootAll()` (виклик `boot()` вже
> зареєстрованих плагінів) відповідно викликається пізніше, у `boot()` пакета, з тим самим
> `app.debug`-гейтом. **Якщо ви самі пишете код, що звертається до БД під час
> discovery/реєстрації власних розширень (не лише плагінів Nexus) — виконуйте його з `boot()`
> свого `ServiceProvider`, а не з `register()`.** Порушення цього порядку не кидає виняток —
> DB-перевірка просто мовчки "провалюється" (типово в бік fail-open, тобто нібито "все увімкнено"),
> що складно відловити пізніше.

Виняток, кинутий під час discovery/`register()`/`boot()` одного плагіна, прокидається далі лише
якщо `app.debug === true`; інакше він логується через `report()` і проковтується — тобто один
зламаний плагін не покладе всю адмінку в продакшені. Це не привід ігнорувати помилку — перевіряйте
логи, тиша ще не означає успіх.

## Готовий приклад

`app/Nexus/Plugins/ConfirmFixtureNote/ConfirmFixtureNotePlugin.php` — робочий приклад
`#[AttachField]`/`#[AttachColumn]`/`#[AttachFilter]` (додає relationManager-поле "notes",
лічильник "notes_count" і пошуковий фільтр "notes_search" на модуль `ConfirmFixture`, яким не
володіє), у парі з data-шаром у `Relations/ConfirmFixtureRelations.php` того ж модуля.
Покрито тестом `tests/Feature/Nexus/AttachFieldColumnTest.php`.

`app/Nexus/Plugins/Example/ExamplePlugin.php` — робочий приклад повного циклу
discover → registerAll/bootAll → apply(): `handle()`, що мутує лейбл пункту меню, `#[Filter]`
на `nexus.validation.rules`, обмежений модулем `Demo`, і `#[Action]`, що реєструє аліас
типу поля. Покрито тестом `tests/Feature/Nexus/PluginSystemTest.php`.

`app/Nexus/Plugins/GraphQL/GraphQLPlugin.php` і `app/Nexus/Plugins/BlockTypes/BlockTypesPlugin.php`
— приклади плагінів-якорів (`#[TargetModule('User')]` з порожнім `handle()`), уся робота яких —
у `boot()` (реєстрація маршруту GraphQL API та типів блоків редактора відповідно).
