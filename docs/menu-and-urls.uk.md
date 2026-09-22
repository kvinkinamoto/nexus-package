# Меню та URL-резолвери

Документ описує механізм пакета `nodex/nexus`, який дозволяє записам
довільного модуля резолвитись у реальний фронтенд-URL, а адмінському сайдбар-
меню — динамічно поповнюватись пунктами без хардкоду роутів. Наприкінці —
розбір трьох модулів (`Menu`, `Redirect`, `Sitemap`), які використовують цей
механізм на практиці. Ці три модулі живуть у застосунку
(`app/Nexus/Modules/{Menu,Redirect,Sitemap}`), а не в самому пакеті — вони
наведені як робочий приклад використання, а не як частина того, що `nodex/nexus`
постачає "з коробки".

## Навіщо це потрібно

Пакет нічого не знає наперед про роути конкретного застосунку: одна інсталяція
може показувати статтю блогу за `/blog/{slug}`, інша — за `/news/{slug}`. Щоб
адмінка (меню, поле "пов'язана сутність" тощо) могла побудувати посилання на
запис будь-якого модуля, не хардкодячи ці роути в самому пакеті, кожен модуль
може заявити **власний резолвер**, який знає, як саме з моделі отримати
публічний URL. Усе інше — `RelatedEntityFieldService`, поле `RelatedEntityField`,
адмінське меню Menu-модуля — працює з цим резолвером узагальнено, через
спільний контракт `UrlResolverInterface`, і не має жодного знання про
конкретні модулі одне одного.

## `#[Module(menuResolver: ...)]`

Атрибут `#[Module]` (`packages/nodex/nexus/src/Attributes/Module.php`) приймає
параметр:

```php
/** Custom menu resolver class */
public readonly ?string $menuResolver = null,
```

Тобто це просто **FQCN класу-резолвера** (рядок або `null`, за замовчуванням
вимкнено). `AttributeSchemaReader::applyModuleMeta()`
(`packages/nodex/nexus/src/Services/AttributeSchemaReader.php:130-132`) читає
це значення й, якщо воно задане, кладе його в конфіг модуля:

```php
if ($moduleMeta->menuResolver) {
    $config->resolver('menu', $moduleMeta->menuResolver);
}
```

Це записується під ключем `'menu'` в `DefaultModuleConfigurationDto::$resolvers`
(масив `string => string`, ім'я резолвера => FQCN), звідки далі його читає
`RelatedEntityFieldService::resolveUrl()` через
`ModuleManager::getModuleConfig($name)->resolvers['menu']`.

Приклад декларації на моделі модуля:

```php
#[Module(
    name: 'page',
    label: 'Сторінки',
    icon: 'solar:document-text-bold',
    group: 'Content',
    showInMenu: true,
    livewire: true,
    menuResolver: PageMenuResolver::class,
)]
class Page extends Model { ... }
```

## `UrlResolverInterface`

Контракт визначено в `packages/nodex/nexus/src/Contracts/UrlResolverInterface.php`:

```php
namespace Nodex\Nexus\Contracts;

use Illuminate\Database\Eloquent\Model;

interface UrlResolverInterface
{
    public function resolve(Model $model): ?string;
}
```

Один метод, `resolve(Model $model): ?string` — приймає конкретний екземпляр
моделі модуля й повертає публічний URL цього запису, або `null`, якщо
побудувати URL неможливо (роут не зареєстровано, модуль вимкнено тощо). Клас,
переданий у `menuResolver`, має реалізовувати саме цей інтерфейс і бути
резолвним через контейнер (`app($resolverClass)`), бо саме так його викликає
`RelatedEntityFieldService::resolveUrl()`.

## `RelatedEntityFieldService` і `RelatedEntityField`

`RelatedEntityFieldService` (`packages/nodex/nexus/src/Services/RelatedEntityFieldService.php`)
— загальний сервіс для роботи з полем типу "пов'язана сутність". У нього три
методи:

- **`getLinkOptions(array $allowedModules, array $excludedModules, ?string $morphId, string $customFieldName, ?string $urlLabel = null): array`**
  — список опцій для `<select>` вибору модуля-цілі (повертає `RelatedEntityOptionDto[]`).
  За замовчуванням виключає службові модулі (`modules`, `permission`, `role`,
  `activityLog`, `auth`, `translations` тощо, див. `$defaultExcluded`).
- **`getEntities(string $modelClass, ?string $labelField = null): array`**
  — мапа `[id => label]` записів обраної моделі для підвантаження в `<select>`
  (AJAX). Сам вгадує підпис (`title`/`name`/`id`) і, якщо в моделі є
  `scopeIsPublished()` або колонка `is_published`, фільтрує лише опубліковані.
- **`resolveUrl(Model $model): ?string`** — та сама "друга половина" механізму:
  за класом моделі знаходить увімкнений модуль, який на неї забіндений
  (`$config->model === get_class($model)`), дістає `$config->resolvers['menu']`
  і, якщо резолвер заданий і клас існує, викликає `app($resolverClass)->resolve($model)`.
  Якщо резолвера немає, модуль вимкнено або клас не існує — повертає `null`,
  а не кидає виняток (щоб зламане посилання не валило сторінку, на якій воно
  рендериться).

`RelatedEntityField` (`packages/nodex/nexus/src/Fields/RelatedEntityField.php`)
— кастомний тип поля (`CustomFieldTypeInterface`) для поліморфної пари
`{morphType}`/`{morphId}` у формі модуля: адмін обирає модуль-ціль, потім
конкретний запис цього модуля (або, якщо дозволено `urlLabel`, вводить пряме
посилання). Приклад використання з докблоку класу:

```php
$form->field('sliderable_type', 'userType', 'section', 'Link')
    ->default(
        RelatedEntityField::make(
            morphType: 'sliderable_type',
            morphId:   'sliderable_id',
            moduleName: 'slider',
            allowedModules: ['shopCategory', 'blogPost'],
            urlLabel: null, // null = без опції "URL"
        )
    )
    ->required(false);
```

Внутрішньо `RelatedEntityField::getCustomData()`/`getDefaultValue()`
делегують у `RelatedEntityFieldService::getLinkOptions()`/`getEntities()`, а
підвантаження списку записів обраного модуля відбувається через AJAX-екшн
`getRelatedItems` (`NexusController::getRelatedItems()`), на який
`RelatedEntityField` сам будує `route('nexus.module.action', ['module' => ..., 'action' => 'getRelatedItems'])`.

## Подія `SidebarMenuBuilding`

`ModuleServiceForAdminPanel::getSideBarMenu()`
(`packages/nodex/nexus/src/Services/ModuleServiceForAdminPanel.php`) будує
пункти сайдбар-меню для кожного увімкненого модуля (з перевіркою permission
`AdminPanelPermissionEnum::SHOW_SIDE_MENU`), після чого дає можливість
довизначити список ще двома способами — Laravel-подією й плагінським
фільтром:

```php
// Lets a plugin append an entirely new top-level menu entry (not
// tied to any single module's own #[Module] config) or re-order/
// drop what's already here.
event(new SidebarMenuBuilding($menu));

return nexus_filter('nexus.menu.sidebar', $menu);
```

`SidebarMenuBuilding` (`packages/nodex/nexus/src/Events/SidebarMenuBuilding.php`)
— звичайна Laravel-подія з масивом `$menu` (елементи — `MenuConfigDto`),
переданим **по посиланню**:

```php
class SidebarMenuBuilding
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public array &$menu
    ) {
    }
}
```

Листенер може додати новий пункт меню, не прив'язаний до жодного конкретного
`#[Module]` (наприклад, звіти/дашборд, який не є окремим CRUD-модулем), або
змінити/видалити наявні пункти:

```php
class AppendReportsMenuEntry
{
    public function handle(SidebarMenuBuilding $event): void
    {
        $event->menu[] = new \Nodex\Nexus\Dto\ModuleDtos\MenuConfigDto(
            name: 'reports',
            label: 'Reports',
        );
    }
}
```

`MenuConfigDto` (`packages/nodex/nexus/src/Dto/ModuleDtos/MenuConfigDto.php`)
має поля `name`, `label`, `show`, `parent`, `icon` — той самий формат, що й
пункти, автоматично згенеровані з `#[Module(...)]`.

Після події той самий масив ще проходить через плагінський фільтр
`nexus_filter('nexus.menu.sidebar', $menu)` — це вже механізм плагінів
(`HookManager`), не частина документа про URL-резолвери; згадано тут лише
тому, що спрацьовує одразу після `SidebarMenuBuilding` у тому самому методі.

## Приклад: модулі `Menu`, `Redirect`, `Sitemap` (рівень застосунку)

⚠️ Ці три модулі лежать у `app/Nexus/Modules/{Menu,Redirect,Sitemap}` цього
конкретного проєкту, а **не в самому пакеті** `nodex/nexus`. Вони не
постачаються "з коробки" разом із пакетом (на відміну від `Auth`/`User`/
`Permission`/`Role` з `Instalation.md`) — це прикладна реалізація поверх
описаного вище механізму.

### Menu

`Menu`/`MenuItem` (`app/Nexus/Modules/Menu/Models/{Menu,MenuItem}.php`) — це
не резолвер, а **споживач** механізму: кожен `MenuItem` або зберігає прямий
URL, або поліморфно (`linkable_type`/`linkable_id`) вказує на запис іншого
модуля. Метод `MenuItem::resolvedUrl()` реалізує саме той пріоритет, що
описаний у докблоці класу — ручний `url` завжди виграє, інакше URL
резолвиться через `RelatedEntityFieldService`:

```php
public function resolvedUrl(): string
{
    if ($this->url) {
        return $this->url;
    }

    if ($this->linkable) {
        $url = app(RelatedEntityFieldService::class)->resolveUrl($this->linkable);
        if ($url) {
            return $url;
        }
    }

    return '#';
}
```

Форма додавання пункту меню (`MenuItemsManager::linkModuleOptions()`,
`app/Nexus/Modules/Menu/Livewire/MenuItemsManager.php`) навмисно показує в
списку "прив'язати до сутності" **лише ті модулі, які задекларували
`menuResolver`** — інакше вибраний запис не мав би, куди резолвитись:

```php
public function linkModuleOptions(): array
{
    $options = app(RelatedEntityFieldService::class)->getLinkOptions(
        allowedModules: [],
        excludedModules: [],
        morphId: 'linkable',
        customFieldName: 'linkable',
    );

    $linkable = [];
    foreach ($options as $option) {
        $config = ModuleManager::getModuleConfig($option->name());
        if (! empty($config->resolvers['menu'] ?? null)) {
            $linkable[$option->name()] = $option->label() ?? $option->name();
        }
    }

    return $linkable;
}
```

У цьому проєкті `menuResolver` реально задекларовано на модулях `Page`
(`app/Nexus/Modules/Page/Models/Page.php`) і `BlogPost`
(`app/Nexus/Modules/BlogPost/Models/BlogPost.php`), кожен зі своїм резолвером,
що реалізує саме пакетний `UrlResolverInterface`:

```php
// app/Nexus/Modules/Page/Services/PageMenuResolver.php
class PageMenuResolver implements UrlResolverInterface
{
    public function resolve(Model $model): ?string
    {
        if (! app('router')->has('nexus.page.show')) {
            return null;
        }

        return route('nexus.page.show', $model->slug);
    }
}
```

```php
#[Module(
    name: 'page',
    // ...
    menuResolver: PageMenuResolver::class,
)]
class Page extends Model { ... }
```

`BlogPostMenuResolver` (`app/Nexus/Modules/BlogPost/Services/BlogPostMenuResolver.php`)
влаштований дзеркально, через роут `nexus.blogPost.show`. Це і є той самий
"порожній" механізм пакета (`menuResolver`/`UrlResolverInterface`), доведений
до робочого стану на рівні застосунку — самі `Page`/`BlogPost` не належать
пакету `nodex/nexus`.

> Примітка щодо перевірки: у пакетному стабі `src/AppStubs/User.php.stub`
> (і в опублікованому з нього `app/Models/User.php`) також є
> `menuResolver: 'App\Nexus\Modules\User\Services\UserMenuResolver'`. Клас
> `UserMenuResolver` (`src/Modules/User/Services/UserMenuResolver.php`, і
> його опублікована копія в `app/Nexus/Modules/User/Services/`) раніше
> імпортував неіснуючий `App\Nexus\Modules\Menu\Contracts\MenuUrlResolverInterface`
> — такого файлу не було ні в пакеті, ні в жодному сусідньому проєкті на
> цій машині, що перевіряв(-ла) ці самі 4 стартові модулі. **Виправлено**:
> клас тепер реалізує пакетний `Nodex\Nexus\Contracts\UrlResolverInterface`
> (сигнатура `resolve(Model $model): ?string` була ідентична, тож це просто
> заміна імпорту й `implements`, без зміни логіки). `User` тепер так само
> робочий приклад `menuResolver`, як `Page`/`BlogPost`.

### Redirect

`Redirect` (`app/Nexus/Modules/Redirect/Models/Redirect.php`) — звичайний
CRUD-модуль (`from_path` → `to_path` + `status_code` через enum
`RedirectStatusCode`) з окремим `RedirectMiddleware`
(`app/Nexus/Modules/Redirect/Http/Middleware/RedirectMiddleware.php`).
`menuResolver` на ньому **не заданий** — записи `Redirect` самі є парою
шляхів, а не сутністю, на яку резолвиться посилання з іншого місця, тож
механізм менюрезолвера тут не задіяний.

### Sitemap

`SitemapCustomUrl` (`app/Nexus/Modules/Sitemap/Models/SitemapCustomUrl.php`)
— адмінський модуль для додаткових URL сайтемапу (записи без власного
модуля-джерела: зовнішня посадкова сторінка, легасі-шлях, залишений заради
SEO). Так само не використовує `menuResolver` напряму. Основний
sitemap-генератор (`app/Nexus/Modules/Sitemap/Generators/AbstractSitemapGenerator.php`,
`Services/SitemapAutoService.php`) обходить усі модулі й для кожного будує
власний генератор — це окремий механізм від описаного вище URL-резолвера
меню, хоч і розв'язує суміжну задачу ("яка публічна URL-адреса відповідає
цьому запису").

## Залежність `spatie/laravel-sitemap`

Пакет `spatie/laravel-sitemap` заявлений як пряма залежність у
**`composer.json` самого пакета** `nodex/nexus`
(`packages/nodex/nexus/composer.json`), а не в кореневому `composer.json`
застосунку:

```json
"require": {
    ...
    "spatie/laravel-sitemap": "^7.2 || ^8.0"
}
```

Це свідоме рішення: модуль `Sitemap` (хоч і живе на рівні застосунку в цьому
проєкті) спирається на функціонал пакета, тож і сама залежність має
подорожувати разом із пакетом, а не додаватись окремо в кожному застосунку,
що його підключає.
