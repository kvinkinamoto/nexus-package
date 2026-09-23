<h2 style="color:#ba363f">Modules</h2>

Модуль у Nexus — це самодостатній тип контенту для адмінки: Eloquent-модель,
чия форма створення/редагування, таблиця списку, валідація та (за потреби)
API-ресурс описуються PHP 8 атрибутами прямо на класі моделі. Жодного
окремого `ModuleConfiguration`-білдера писати не треба — атрибути читає
`AttributeSchemaReader` і збирає з них DTO, яким далі керується вся адмінка
(форма, таблиця, ajax-релейшени, валідація). Клас-`ModuleConfiguration` як
окрема сутність усе ще підтримується (`#[Module]` можна повісити не на саму
модель, а на конфіг-клас поруч — див. параметр `model` нижче), але в реальних
модулях пакета він не використовується: атрибути завжди на моделі.

Модуль автоматично реєструється в системі, щойно `ModuleRegistry` знаходить
клас із `#[Module(...)]` у ввімкненому каталозі `Modules/**` — окремого
`ModuleServiceProvider` для кожного модуля писати не треба.

Готові та додаткові модулі до пакету можна знайти на офіційному сайті проєкту: [https://www.nexus-cms.shop/](https://www.nexus-cms.shop/).

## 1. Мінімальний робочий приклад

Нижче — спрощена, але повністю робоча модель модуля, побудована за
конвенціями, які реально використовуються в пакеті (див. `Modules/Form/Models/Form.php`):

```php
<?php

namespace App\Nexus\Modules\Demo\Models;

use App\Nexus\Modules\Demo\Requests\AdminStoreRequest;
use App\Nexus\Modules\Demo\Requests\AdminUpdateRequest;
use Illuminate\Database\Eloquent\Model;
use Nodex\Nexus\Attributes\Column;
use Nodex\Nexus\Attributes\Field;
use Nodex\Nexus\Attributes\Module;
use Nodex\Nexus\Attributes\Requests;
use Nodex\Nexus\Attributes\Section;
use Nodex\Nexus\Attributes\TableAction;
use Nodex\Nexus\Attributes\TableGroupAction;
use Nodex\Nexus\Concerns\HasAttributeSchemaProperties;

#[Module(
    name: 'demo',
    label: 'Demo',
    icon: 'solar:box-bold',
    group: 'Site',
    showInMenu: true,
    livewire: true,
)]
#[TableAction(name: 'edit', label: 'Edit', icon: 'edit', isConfirm: false)]
#[TableAction(name: 'delete', label: 'Delete', icon: 'delete', isConfirm: true)]
#[TableGroupAction(name: 'deleteGroup', fieldName: 'delete')]
#[Requests(actions: ['store' => AdminStoreRequest::class, 'update' => AdminUpdateRequest::class])]
#[Section(name: 'main', column: 'right', type: 'base', icon: 'solar:box-bold')]
class Demo extends Model
{
    use HasAttributeSchemaProperties;

    #[Column(label: 'Name', sortable: true, searchable: true)]
    #[Field(type: 'string', section: 'main', label: 'Name', required: true)]
    protected $name;

    protected $fillable = ['name'];
}
```

Це саме те, що генерує `php artisan nexus:make:module Demo` (див. розділ 9).
Трейт `HasAttributeSchemaProperties` — обов'язковий на кожній
атрибут-керованій моделі модуля, саме він дозволяє звертатись до `$name` як
до звичайної властивості моделі, попри те що вона оголошена `protected`.

## 2. `#[Module(...)]`

Атрибут вішається на клас моделі (`Attribute::TARGET_CLASS`) і декларує сам
факт існування модуля.

| Параметр | Тип | За замовч. | Опис |
| --- | --- | --- | --- |
| `name` | `string` | — (обов'язковий) | Унікальне camelCase ім'я модуля, напр. `'article'`, `'shopProduct'`. |
| `label` | `string` | `''` | Людська назва, що показується в меню адмінки. |
| `icon` | `string` | `'solar:box-bold'` | Іконка (Solar Icons або FontAwesome). |
| `group` | `string` | `'Site'` | Батьківська група в меню, напр. `'Shop'`, `'Users'`, `'Content'`. |
| `showInMenu` | `bool` | `true` | Показувати модуль у боковому меню. |
| `permissions` | `bool` | `true` | Автоматично генерувати CRUD-права для модуля. |
| `isTree` | `bool` | `false` | Чи модуль представляє деревовидну структуру. |
| `menuResolver` | `?string` | `null` | Клас кастомного резолвера пунктів меню (реалізує `UrlResolverInterface`). Реальний приклад — `App\Nexus\Modules\User\Services\UserMenuResolver`. |
| `model` | `?string` | `null` | Явний клас Eloquent-моделі, якщо `#[Module]` повішено не на саму модель (наприклад, окремий `ModuleConfiguration`-клас над вендорською моделлю). За замовчуванням — сам анотований клас. |
| `requires` | `array` | `[]` | Імена інших модулів, потрібних для роботи (напр. `Wishlist` вимагає `ShopProduct`). Суто інформаційне — **не блокує** інсталяцію чи ввімкнення; відсутню залежність підсвічує банер в адмінці (`ModuleDependencyChecker`). |
| `wizard` | `bool` | `false` | Рендерити форму як покроковий wizard замість однієї сторінки. Потребує хоча б одного `#[Section(tab:)]` — наявні вкладки стають кроками з навігацією Next/Back. |
| `slideOver` | `bool` | `false` | Відкривати форму редагування в offcanvas-панелі зі списку замість переходу на окрему сторінку. |
| `livewire` | `bool` | `false` | Історичний прапорець поетапного переходу на Livewire — на сьогодні **неактивний**, жодна логіка контролера на нього не реагує. Залишений для зворотної сумісності зі старими оголошеннями `#[Module(...)]`. |

```php
#[Module(name: 'article', label: 'Статті', icon: 'solar:document-bold', group: 'Content')]
class Article extends Model { ... }
```

## 3. `#[Field(...)]`

Вішається на публічну/protected властивість або на метод-релейшен
(`TARGET_PROPERTY | TARGET_METHOD`) і оголошує поле форми.

| Параметр | Тип | За замовч. | Опис |
| --- | --- | --- | --- |
| `type` | `string` | — (обов'язковий) | Тип поля. Повний перелік вбудованих типів і що кожен з них рендерить — див. [`docs/field-types.md`](./field-types.md). |
| `section` | `string` | `'main'` | Ключ секції форми, до якої належить поле (див. `#[Section]`). |
| `label` | `?string` | `null` | Людська назва. Якщо `null` — генерується з імені властивості. |
| `required` | `bool` | `true` | Чи поле обов'язкове. |
| `translated` | `bool` | `false` | Чи поле мультимовне (потребує `Spatie\Translatable\HasTranslations` + `public $translatable` на моделі). |
| `editor` | `bool` | `false` | Увімкнути WYSIWYG-редактор (застосовується для `type: 'text'`). |
| `default` | `mixed` | `null` | Значення за замовчуванням. |
| `enum` | `?string` | `null` | Клас backed-enum'а для опцій `select`/`radio`/`enum`. |
| `action` | `?string` | `null` | Назва дії для інтерактивних полів (напр. `'boolToggle'`). |
| `actionField` | `?string` | `null` | Назва поля в БД для дії, якщо відрізняється від імені властивості. |
| `disabled` | `bool\|string` | `false` | Заблокувати поле. Рядкове значення `'create'` або `'edit'` обмежує блокування лише цим контекстом. |
| `view` | `?string` | `null` | Для `type: 'view'` — Blade-в'ю `namespace::path`, у яку передаються `$model`, `$field`, `$module`. |
| `relationConfig` | `array` | `[]` | Для `type: 'relation'` — додаткові override-параметри, напр. `['path_field' => 'path']`. |
| `order` | `int` | `0` | Порядок виводу в секції (менше — вище). За замовчуванням — порядок оголошення в класі. |
| `rules` | `string\|array` | `[]` | Правила валідації для store і update одразу. Рядок через `\|` або масив. Для перекладних полів автоматично застосовується до кожної локалі (`field.*`). |
| `storeRules` | `string\|array` | `[]` | Правила лише для store (перекривають `rules`). |
| `updateRules` | `string\|array` | `[]` | Правила лише для update (перекривають `rules`). |
| `showWhen` | `array` | `[]` | Умови показу поля: масив `['field' => '...', 'op' => 'eq\|neq\|in\|notIn\|truthy\|falsy\|gt\|lt\|contains', 'value' => ...]`. Порожньо — завжди показане. |
| `showWhenLogic` | `string` | `'and'` | Як комбінуються кілька умов `showWhen`: `'and'` або `'or'`. |
| `clearWhenHidden` | `bool` | `false` | Очищати значення на клієнті, коли поле ховається. |
| `apiExpose` | `bool` | `false` | Чи включати поле в авто-згенерований API-ресурс (`NexusResource::toArray()`). За замовчуванням поле лише адмінське. |
| `showInInfolist` | `bool` | `true` | Чи показувати поле на read-only екрані перегляду (Infolist). Вимикайте для технічних/чутливих полів (напр. `password`). |
| `slugSource` | `?string` | `null` | Для `type: 'slug'` — ім'я іншого поля цієї ж форми, значення якого слагіфікує кнопка "generate". |

```php
#[Column(label: 'Title', sortable: true, searchable: true)]
#[Field(type: 'string', section: 'main', label: 'Title', required: true, rules: ['max:255'])]
protected $title;
```

### Як з `#[Field]` виводяться правила валідації

Якщо модуль **не** оголошує `#[Requests(...)]`, збіркою валідаційних правил
займається `Nodex\Nexus\Services\Validation\NexusRuleCollector`. Для кожного
не-релейшен поля порядок такий:

1. Дефолтні правила типу (`FieldTypeRegistry::getDefaultRules($field->type)`).
2. `rules`, потім `storeRules`/`updateRules` (залежно від дії) — додаються поверх.
3. Якщо серед правил немає ні `required`, ні `nullable` — підставляється
   `required` (коли `required: true`) або `nullable`.
4. Для перекладного поля (`translated: true`) ключ стає `{name}.*`.
5. Для `showWhen`-поля, прихованого за поточних вхідних значень, правило
   форсовано замінюється на `['exclude']`.

Для relation-полів (`type: 'relation'` або поля з `#[RepeaterField]`)
збирач сам не знає ні зв'язаної таблиці, ні структури pivot-даних — він
підставляє лише мінімальний baseline (`required`/`nullable`, плюс `array`
для множинних зв'язків), достатній, щоб ключ `relation.{name}` узагалі
пережив Laravel'івський `validated()`.

**Важливо:** щойно модуль оголошує `#[Requests(actions: [...])]`, поле-рівневі
`rules`/`storeRules`/`updateRules` для дій `store`/`update` **більше не
враховуються** — `rules()` відповідного Request-класу мусить самостійно
покривати кожне поле, включно з relation-полями у вигляді `relation.*`-ключів
(`'relation.tags' => 'nullable|array'`, `'relation.tags.*' => 'integer'`).
Незгаданий relation-ключ валідацію мовчки пропускає, і зв'язок просто не
збережеться — без жодної помилки. Щоб уникнути ручного дублювання
`NexusRuleCollector`-логіки в такому Request-класі, підключіть трейт
`Nodex\Nexus\Concerns\ComposesNexusRules` — він викликає `NexusRuleCollector`
всередині `rules()` і дає перекрити лише те, чого збирач не вміє
(`extraRules()`: unique-перевірки, `Rule::exists()`, крос-польова логіка).

## 4. `#[Column(...)]`

Вішається на ту ж властивість/метод, що й `#[Field]` (необов'язково — можна
мати `#[Field]` без `#[Column]`, якщо поле не має бути в таблиці списку).
Конфігурує колонку в таблиці списку модуля.

| Параметр | Тип | За замовч. | Опис |
| --- | --- | --- | --- |
| `label` | `?string` | `null` | Заголовок колонки. |
| `sortable` | `bool` | `false` | Чи колонка сортується. |
| `action` | `?string` | `null` | Спеціальна інтерактивна дія в колонці. Вбудовані значення: `'boolToggle'`, `'ordering'`. |
| `fieldName` | `?string` | `null` | Назва поля в БД, якщо відрізняється від імені властивості — використовується дією `boolToggle`, щоб знати, яку колонку оновлювати. |
| `customField` | `?string` | `null` | Назва кастомного Blade-партіалу з `templates/custom_index_fields/` для рендеру цієї клітинки. |
| `actionConfirm` | `bool` | `false` | Показувати діалог підтвердження перед виконанням дії. |
| `tableDefault` | `bool` | `true` | Чи колонка видима в таблиці за замовчуванням (впливає на пікер видимості колонок для користувача). |
| `order` | `int` | `0` | Порядок у таблиці (менше — лівіше). За замовчуванням — порядок оголошення в класі. |
| `searchable` | `bool` | `false` | Явний opt-in у глобальний крос-модульний пошук (`GlobalSearchService`). На відміну від пер-модульного фільтра пошуку, який LIKE-матчить усі фізичні колонки, глобальний пошук вимагає явної позначки, щоб не "протікали" внутрішні/нерелевантні колонки. |

```php
#[Column(label: 'Активний', action: 'boolToggle', fieldName: 'is_active')]
#[Field(type: 'boolean', section: 'settings')]
public bool $is_active;
```

## 5. `#[Section]` / `#[SectionColumn]`

`#[Section]` вішається на клас моделі (повторюваний, `IS_REPEATABLE`) і
декларує іменовану секцію форми та її позицію в сітці макета.

| Параметр | Тип | За замовч. | Опис |
| --- | --- | --- | --- |
| `name` | `string` | — (обов'язковий) | Унікальний ключ секції, на який посилається `#[Field(section: '...')]`. |
| `column` | `string` | `'right'` | Колонка макета, до якої належить секція. Має збігатися з ім'ям `SectionColumn` (`'left'`, `'right'` або кастомне). |
| `type` | `string` | `'base'` | Тип рендеру секції (мапиться на Blade-партіал у `templates/sections/`). Вбудовані значення: `'base'`, `'columns_2'`, `'information'`. |
| `icon` | `string` | `'solar:box-bold'` | Іконка заголовка картки секції. |
| `tab` | `?string` | `null` | Ключ вкладки, до якої прив'язується колонка цієї секції. |

```php
#[Section(name: 'main',     column: 'left',  type: 'columns_2', icon: 'solar:document-bold')]
#[Section(name: 'settings', column: 'right', type: 'base',      icon: 'solar:settings-bold')]
```

`#[SectionColumn]` (теж на класі, повторюваний) перевизначає CSS-клас
(ширину) колонки макета. За замовчуванням існують `'left'`/`'right'` з
класами `col-lg-8`/`col-lg-4`.

| Параметр | Тип | Опис |
| --- | --- | --- |
| `name` | `string` | Ім'я колонки, що перевизначається. |
| `class` | `string` | CSS-клас (напр. `'col-lg-8'`). |
| `tab` | `?string` | Опційна прив'язка до конкретної вкладки. |

```php
#[SectionColumn(name: 'left', class: 'col-lg-8')]
#[SectionColumn(name: 'right', class: 'col-lg-4')]
```

## 6. `#[Relation]`

Вішається поруч із `#[Field(type: 'relation'|'images'|'repeater'|...)]` на
метод (або властивість — для релейшена, що приходить з трейта, як
`Spatie\Permission`'s `HasRoles::roles()`, і тому не має власного методу в
моделі; у такому разі `type` треба вказувати явно). Конфігурує, як
Eloquent-релейшен подається в адмінці.

| Параметр | Тип | За замовч. | Опис |
| --- | --- | --- | --- |
| `type` | `?string` | `null` | Тип релейшена: `'belongsTo'`, `'hasMany'`, `'belongsToMany'`, `'hasOne'`. Якщо не вказано — Nexus намагається визначити автоматично з return-типу методу. |
| `show` | `string` | `'name'` | Поле зв'язаної моделі, яке показується в селектах і лейблах. |
| `showFallback` | `?string` | `null` | Поле-фолбек (і в лейблі, і в пошуку), коли значення `show` порожнє для конкретного запису. |
| `required` | `bool` | `false` | Чи вибір значення обов'язковий. |
| `ajax` | `bool` | `false` | `false` — список опцій підвантажується наперед (до 50), поводиться як звичайний select (годиться для невеликих таблиць: ролі, категорії). `true` — нічого не підвантажується наперед, результати з'являються лише при введенні (якщо не задано `ajaxMode: 'load'`). |
| `ajaxMode` | `string` | `'search'` | Актуально лише при `ajax: true`. `'search'` — результати лише за пошуковим запитом (для великих таблиць). `'load'` — все підвантажується наперед і одночасно підтримує пошук (для середніх таблиць). |
| `ajaxResource` | `?string` | `null` | Кастомний API Resource клас для трансформації ajax-відповіді. |
| `relatedModule` | `?string` | `null` | Власне ім'я nexus-модуля зв'язаної моделі — обов'язкове для `#[Field(type: 'relationManager')]`, дозволяє зв'язати рядки з edit/delete-діями того модуля й побудувати посилання "view all", відфільтроване на батьківський запис. |

```php
// BelongsToMany, невелика таблиця — переладжується як select
#[Field(type: 'relation', section: 'roles_and_permissions')]
#[Relation(type: 'belongsToMany', show: 'name')]
public function roles(): BelongsToMany { ... }

// BelongsTo з ajax-пошуком, велика таблиця
#[Field(type: 'relation', section: 'settings')]
#[Relation(type: 'belongsTo', show: 'name', ajax: true, ajaxMode: 'search')]
public function category(): BelongsTo { ... }
```

## 7. `#[RepeaterField]`

Декларує одну колонку поля типу `repeater` (`TARGET_METHOD`, повторюваний) —
складається стеком на тому самому `hasMany`-методі, одна колонка на кожен
`#[RepeaterField]`, у порядку оголошення. Сам relation-метод усе одно
потребує `#[Field(type: 'repeater', ...)]` + `#[Relation(type: 'hasMany', ...)]`
як у будь-якого іншого `HasMany` — `#[RepeaterField]` лише додає метадані
колонок поверх.

| Параметр | Тип | За замовч. | Опис |
| --- | --- | --- | --- |
| `name` | `string` | — (обов'язковий) | Ключ колонки — на сабміті стає `relation[{relationName}][{index}][{name}]`. |
| `type` | `string` | — (обов'язковий) | Тип клітинки, розв'язується так само, як і будь-який `#[Field]` тип (override модуля → реєстр → вбудований). |
| `label` | `?string` | `null` | Заголовок колонки. Автогенерується з `name`, якщо `null`. |
| `required` | `bool` | `false` | Чи обов'язкова колонка. |
| `rules` | `string\|array` | `[]` | Правила валідації для цієї колонки — застосовуються `NexusRuleCollector`'ом як `relation.{name}.*.{column}`. |
| `width` | `?string` | `null` | CSS-ширина `<th>`/`<td>`, напр. `'120px'` або `'20%'`. |
| `showWhen` | `array` | `[]` | Наразі зібрано, але ще не застосовується функціонально (заплановано для по-рядкової умовної видимості). |
| `showWhenLogic` | `string` | `'and'` | Логіка комбінування `showWhen`. |

```php
#[Field(type: 'repeater', section: 'fields_section', label: 'Fields')]
#[Relation(type: 'hasMany')]
#[RepeaterField(name: 'key', type: 'string', label: 'Key', required: true, width: '160px')]
#[RepeaterField(name: 'label', type: 'string', label: 'Label', required: true)]
#[RepeaterField(name: 'type', type: 'string', label: 'Type', required: true, width: '140px')]
#[RepeaterField(name: 'required', type: 'boolean', label: 'Required', width: '90px')]
public function fields(): HasMany
{
    return $this->hasMany(FormField::class)->orderBy('order');
}
```

## 8. Допоміжні атрибути

### `#[Permission]`

Реєструє кастомний permission для дії модуля, щоб він з'явився в панелі
керування правами й міг призначатись ролям/юзерам звідти. **Не** призначає
право нікому автоматично — цим займається адмін вручну. Можна вішати на клас
моделі (для глобальних кастомних прав модуля) або на метод кастомного
контролера (для прав конкретної дії).

| Параметр | Тип | За замовч. | Опис |
| --- | --- | --- | --- |
| `action` | `string` | — (обов'язковий) | Ключ дії — стає суфіксом імені права: `{moduleName}_{action}`. |
| `label` | `?string` | `null` | Людський лейбл у панелі прав. Автогенерується з `action`, якщо `null`. |
| `guard` | `string` | `'web'` | Guard, під яким реєструється право. |

```php
#[Permission(action: 'run', label: 'Run backup now')]
```

### `#[Requests]`

Прив'язує кастомні `FormRequest`-класи до дій модуля за ключем дії (`'store'`,
`'update'`, `'restore'`, `'boolToggle'` або будь-яка кастомна назва групової
дії). Резолвиться `GetModuleRequestAction::getRequestByMethodName()` для
будь-якої дії, не лише `store`/`update`. Див. розділ 3 щодо того, як це
впливає на джерело правил валідації.

```php
#[Requests(actions: ['store' => AdminStoreRequest::class, 'update' => AdminUpdateRequest::class])]
```

### `#[Composer]`

Реєструє клас Blade view-composer'а для власних в'ю модуля (клас на класі
моделі, повторюваний).

```php
#[Composer(class: DemoIndexComposer::class)]
```

### `#[MethodResource]`

Прив'язує окремий API Resource-клас (із хінтами eager-load) до одного
кастомного методу контролера, замість того, щоб той метод падав на
загальний `NexusResource`-фолбек.

| Параметр | Тип | Опис |
| --- | --- | --- |
| `method` | `string` | Ім'я методу контролера. |
| `resourceClass` | `string` | Клас API Resource. |
| `with` | `array` | Список зв'язків для eager-load. |

```php
#[MethodResource(method: 'export', resourceClass: DemoExportResource::class, with: ['items'])]
```

## 9. Скафолдинг нового модуля

```
php artisan nexus:make:module {name}
```

Команда приймає ім'я (автоматично приводиться до `UcfirstCamel`) і, якщо
модуля з такою назвою ще не існує в `app/Nexus/Modules/{Name}`, генерує:

- `Models/{Name}.php` — модель з `#[Module]`, базовими `#[TableAction]`
  (`edit`/`delete`), `#[TableGroupAction(deleteGroup)]`, `#[Requests]`,
  однією секцією `main` і одним полем `name` — мінімальний робочий модуль,
  готовий одразу після міграції.
- `Requests/AdminStoreRequest.php`, `Requests/AdminUpdateRequest.php` —
  порожні `NexusFormRequest`-нащадки з правилом `'name' => 'required|string'`.
- `database/migrations/{timestamp}_create_{plural_snake}_table.php`.
- `resources/lang/en/translate.php`, `resources/lang/uk/translate.php`.
- `resources/views/docs.blade.php`.

Далі команда сама підказує наступні кроки:

```
1. Add the fields/relations you need to Models/{Name}.php
2. php artisan migrate
3. php artisan nexus:module:install {Name}
```

`nexus:module:install {name}` (без імені — усі модулі) створює рядок
`Module` у БД, запускає міграції та реєструє власну папку `Widgets/` модуля
в живому реєстрі.

## 10. Кеш маніфесту модулів

Якщо існує файл `bootstrap/cache/nexus-modules.php`, discovery модулів
(а також field-types, widgets, listeners, translations) обслуговується зі
скомпільованого маніфесту, а не з живого сканування файлової системи. Це
означає, що **щойно доданий або змінений модуль не з'явиться**, доки кеш не
перебудувати:

```
php artisan nexus:module:cache   # скомпілювати/перебудувати маніфест негайно
php artisan nexus:module:clear   # видалити маніфест, повернутись до live-сканування
```

Після будь-якої структурної зміни під `app/Nexus/Modules/**` (новий модуль,
новий field type, новий listener) — перевіряйте, чи існує
`bootstrap/cache/nexus-modules.php`, і виконуйте `nexus:module:clear` (або
одразу `nexus:module:cache`, щоб не чекати на live-сканування наступного
запиту).

## Довідка по типах полів

Повний перелік вбудованих `type`-значень для `#[Field]`/`#[RepeaterField]` і
короткий опис кожного — у [`field-types.md`](./field-types.md).
