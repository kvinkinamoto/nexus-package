# Menu and URL Resolvers

This document describes the mechanism in the `nodex/nexus` package that lets
records of any module resolve to a real front-end URL, and lets the admin
sidebar menu be dynamically extended with entries without hardcoding routes.
At the end, there's a walkthrough of three modules (`Menu`, `Redirect`,
`Sitemap`) that use this mechanism in practice. These three modules live in
the application (`app/Nexus/Modules/{Menu,Redirect,Sitemap}`), not in the
package itself — they're presented as a working usage example, not as part
of what `nodex/nexus` ships "out of the box."

## Why this is needed

The package knows nothing in advance about a specific application's routes:
one installation might show a blog post at `/blog/{slug}`, another at
`/news/{slug}`. So that the admin panel (menus, "related entity" fields,
etc.) can build a link to a record of any module without hardcoding those
routes into the package itself, each module can declare its **own
resolver**, which knows exactly how to get a public URL from the model.
Everything else — `RelatedEntityFieldService`, the `RelatedEntityField`
field, the Menu module's admin menu — works with this resolver generically,
through the shared `UrlResolverInterface` contract, with no knowledge of any
other module's specifics.

## `#[Module(menuResolver: ...)]`

The `#[Module]` attribute (`packages/nodex/nexus/src/Attributes/Module.php`)
accepts a parameter:

```php
/** Custom menu resolver class */
public readonly ?string $menuResolver = null,
```

In other words, this is simply the **FQCN of a resolver class** (a string,
or `null`, disabled by default). `AttributeSchemaReader::applyModuleMeta()`
(`packages/nodex/nexus/src/Services/AttributeSchemaReader.php:130-132`) reads
this value and, if it's set, puts it into the module's config:

```php
if ($moduleMeta->menuResolver) {
    $config->resolver('menu', $moduleMeta->menuResolver);
}
```

This is stored under the `'menu'` key in
`DefaultModuleConfigurationDto::$resolvers` (an array of
`string => string`, resolver name => FQCN), from which
`RelatedEntityFieldService::resolveUrl()` later reads it via
`ModuleManager::getModuleConfig($name)->resolvers['menu']`.

Example declaration on a module's model:

```php
#[Module(
    name: 'page',
    label: 'Pages',
    icon: 'solar:document-text-bold',
    group: 'Content',
    showInMenu: true,
    livewire: true,
    menuResolver: PageMenuResolver::class,
)]
class Page extends Model { ... }
```

## `UrlResolverInterface`

The contract is defined in
`packages/nodex/nexus/src/Contracts/UrlResolverInterface.php`:

```php
namespace Nodex\Nexus\Contracts;

use Illuminate\Database\Eloquent\Model;

interface UrlResolverInterface
{
    public function resolve(Model $model): ?string;
}
```

A single method, `resolve(Model $model): ?string` — takes a specific
instance of the module's model and returns the public URL for that record,
or `null` if a URL can't be built (route not registered, module disabled,
etc.). The class passed as `menuResolver` must implement exactly this
interface and be resolvable through the container (`app($resolverClass)`),
because that's exactly how `RelatedEntityFieldService::resolveUrl()` invokes
it.

## `RelatedEntityFieldService` and `RelatedEntityField`

`RelatedEntityFieldService`
(`packages/nodex/nexus/src/Services/RelatedEntityFieldService.php`) is the
general-purpose service for working with the "related entity" field type. It
has three methods:

- **`getLinkOptions(array $allowedModules, array $excludedModules, ?string $morphId, string $customFieldName, ?string $urlLabel = null): array`**
  — a list of options for the `<select>` used to pick the target module
  (returns `RelatedEntityOptionDto[]`). By default it excludes service
  modules (`modules`, `permission`, `role`, `activityLog`, `auth`,
  `translations`, etc.; see `$defaultExcluded`).
- **`getEntities(string $modelClass, ?string $labelField = null): array`**
  — a `[id => label]` map of records of the chosen model, for lazy-loading
  into the `<select>` (via AJAX). It guesses the label itself
  (`title`/`name`/`id`) and, if the model has a `scopeIsPublished()` or an
  `is_published` column, filters to published records only.
- **`resolveUrl(Model $model): ?string`** — the same "second half" of the
  mechanism: given the model's class, it finds the enabled module bound to
  it (`$config->model === get_class($model)`), retrieves
  `$config->resolvers['menu']`, and, if a resolver is set and the class
  exists, calls `app($resolverClass)->resolve($model)`. If there's no
  resolver, the module is disabled, or the class doesn't exist, it returns
  `null` rather than throwing an exception (so a broken link doesn't take
  down the page it's rendered on).

`RelatedEntityField` (`packages/nodex/nexus/src/Fields/RelatedEntityField.php`)
is a custom field type (`CustomFieldTypeInterface`) for a polymorphic
`{morphType}`/`{morphId}` pair in a module's form: the admin picks a target
module, then a specific record of that module (or, if `urlLabel` is
allowed, enters a direct link). Usage example from the class docblock:

```php
$form->field('sliderable_type', 'userType', 'section', 'Link')
    ->default(
        RelatedEntityField::make(
            morphType: 'sliderable_type',
            morphId:   'sliderable_id',
            moduleName: 'slider',
            allowedModules: ['shopCategory', 'blogPost'],
            urlLabel: null, // null = no "URL" option
        )
    )
    ->required(false);
```

Internally, `RelatedEntityField::getCustomData()`/`getDefaultValue()`
delegate to `RelatedEntityFieldService::getLinkOptions()`/`getEntities()`,
and loading the list of records for the chosen module happens via the
`getRelatedItems` AJAX action (`NexusController::getRelatedItems()`), for
which `RelatedEntityField` itself builds
`route('nexus.module.action', ['module' => ..., 'action' => 'getRelatedItems'])`.

## The `SidebarMenuBuilding` event

`ModuleServiceForAdminPanel::getSideBarMenu()`
(`packages/nodex/nexus/src/Services/ModuleServiceForAdminPanel.php`) builds
sidebar menu entries for every enabled module (checking the
`AdminPanelPermissionEnum::SHOW_SIDE_MENU` permission), and then provides two
further ways to refine the list — a Laravel event and a plugin filter:

```php
// Lets a plugin append an entirely new top-level menu entry (not
// tied to any single module's own #[Module] config) or re-order/
// drop what's already here.
event(new SidebarMenuBuilding($menu));

return nexus_filter('nexus.menu.sidebar', $menu);
```

`SidebarMenuBuilding` (`packages/nodex/nexus/src/Events/SidebarMenuBuilding.php`)
is a regular Laravel event with an `$menu` array (elements are
`MenuConfigDto`) passed **by reference**:

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

A listener can add a new menu entry that isn't tied to any particular
`#[Module]` (for example, a reports/dashboard entry that isn't a standalone
CRUD module), or change/remove existing entries:

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
has the fields `name`, `label`, `show`, `parent`, `icon` — the same shape as
the entries auto-generated from `#[Module(...)]`.

After the event, the same array also passes through the plugin filter
`nexus_filter('nexus.menu.sidebar', $menu)` — this is already the plugin
mechanism (`HookManager`), not part of the URL-resolver story; it's
mentioned here only because it fires right after `SidebarMenuBuilding` in
the same method.

## Example: the `Menu`, `Redirect`, `Sitemap` modules (application level)

⚠️ These three modules live in `app/Nexus/Modules/{Menu,Redirect,Sitemap}`
in this particular project, and **not in the `nodex/nexus` package itself**.
They are not shipped "out of the box" with the package (unlike `Auth`/
`User`/`Permission`/`Role` from `Instalation.md`) — this is an applied
implementation on top of the mechanism described above.

### Menu

`Menu`/`MenuItem` (`app/Nexus/Modules/Menu/Models/{Menu,MenuItem}.php`) is
not a resolver but a **consumer** of the mechanism: each `MenuItem` either
stores a direct URL, or polymorphically (`linkable_type`/`linkable_id`)
points to a record of another module. The `MenuItem::resolvedUrl()` method
implements exactly the priority order described in the class's docblock — a
manual `url` always wins, otherwise the URL is resolved via
`RelatedEntityFieldService`:

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

The "add menu entry" form (`MenuItemsManager::linkModuleOptions()`,
`app/Nexus/Modules/Menu/Livewire/MenuItemsManager.php`) deliberately shows in
its "link to an entity" list **only modules that have declared a
`menuResolver`** — otherwise the chosen record would have nothing to resolve
to:

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

In this project, `menuResolver` is actually declared on the `Page`
(`app/Nexus/Modules/Page/Models/Page.php`) and `BlogPost`
(`app/Nexus/Modules/BlogPost/Models/BlogPost.php`) modules, each with its own
resolver implementing the package's `UrlResolverInterface`:

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

`BlogPostMenuResolver`
(`app/Nexus/Modules/BlogPost/Services/BlogPostMenuResolver.php`) is set up
the mirror-image way, via the `nexus.blogPost.show` route. This is the same
"empty" package mechanism (`menuResolver`/`UrlResolverInterface`), brought to
a working state at the application level — `Page`/`BlogPost` themselves do
not belong to the `nodex/nexus` package.

> Verification note: the package stub `src/AppStubs/User.php.stub` (and the
> `app/Models/User.php` published from it) also has
> `menuResolver: 'App\Nexus\Modules\User\Services\UserMenuResolver'`. The
> `UserMenuResolver` class (`src/Modules/User/Services/UserMenuResolver.php`,
> and its published copy in `app/Nexus/Modules/User/Services/`) used to
> import a non-existent
> `App\Nexus\Modules\Menu\Contracts\MenuUrlResolverInterface` — no such file
> existed either in the package or in any neighboring project on this
> machine that checked these same 4 starter modules. **Fixed**: the class
> now implements the package's `Nodex\Nexus\Contracts\UrlResolverInterface`
> (the `resolve(Model $model): ?string` signature was identical, so this was
> just a swap of the import and `implements`, with no logic change). `User`
> is now just as much a working `menuResolver` example as `Page`/`BlogPost`.

### Redirect

`Redirect` (`app/Nexus/Modules/Redirect/Models/Redirect.php`) is a regular
CRUD module (`from_path` → `to_path` + `status_code` via the
`RedirectStatusCode` enum) with a separate `RedirectMiddleware`
(`app/Nexus/Modules/Redirect/Http/Middleware/RedirectMiddleware.php`).
`menuResolver` is **not set** on it — `Redirect` records are themselves a
pair of paths, not an entity that gets linked to from somewhere else, so the
menu-resolver mechanism isn't involved here.

### Sitemap

`SitemapCustomUrl` (`app/Nexus/Modules/Sitemap/Models/SitemapCustomUrl.php`)
is an admin module for extra sitemap URLs (records with no module of their
own as a source: an external landing page, a legacy path kept around for
SEO). It likewise doesn't use `menuResolver` directly. The main sitemap
generator (`app/Nexus/Modules/Sitemap/Generators/AbstractSitemapGenerator.php`,
`Services/SitemapAutoService.php`) walks all modules and builds its own
generator for each — this is a separate mechanism from the menu URL resolver
described above, even though it solves an adjacent problem ("what public URL
corresponds to this record").

## The `spatie/laravel-sitemap` dependency

The `spatie/laravel-sitemap` package is declared as a direct dependency in
the **`composer.json` of the `nodex/nexus` package itself**
(`packages/nodex/nexus/composer.json`), not in the application's root
`composer.json`:

```json
"require": {
    ...
    "spatie/laravel-sitemap": "^7.2 || ^8.0"
}
```

This is a deliberate decision: the `Sitemap` module (even though in this
project it lives at the application level) relies on functionality from the
package, so the dependency itself should travel along with the package
rather than being added separately in every application that installs it.
