# Plugin & Hooks system

This document describes Nexus's extension mechanism that does **not** involve owning a
database table: plugins (`app/Nexus/Plugins/**`). If you need a new content type with its own
table, see the modules documentation — this file is purely about extending *someone else's*
behavior.

## Plugin vs. module

- A **module** (`app/Nexus/Modules/{Name}`) owns its own Eloquent model, migration, and admin CRUD.
- A **plugin** (`app/Nexus/Plugins/{Name}/{Name}Plugin.php`) owns nothing. It either mutates
  the configuration of *someone else's* module (adds a field/column/filter, changes validation
  rules, replaces a menu item), or performs cross-cutting, application-level logic —
  registers a route, a block type, a field-type alias, listens to the lifecycle of entities of
  any module.

A plugin is a plain PHP class that Nexus discovers on its own on every request
(`PluginManager::autoDiscover()`) — there's no need to register it manually in a `ServiceProvider`.

## Quick start

```bash
php artisan nexus:make:plugin ReviewAdmin --module=Product
```

`--module` defaults to `User` and determines the `#[TargetModule]` attribute. The command creates
`app/Nexus/Plugins/ReviewAdmin/ReviewAdminPlugin.php` with the skeleton:

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

Remove the methods you don't need — `PluginManager` calls `register()`/`boot()`/`handle()`
only if they exist.

## `#[TargetModule]` — the mandatory anchor

```php
#[Attribute(Attribute::TARGET_CLASS)]
class TargetModule
{
    public function __construct(public string $name) {}
}
```

A class attribute indicating which module's configuration the plugin mutates. It does two things:

1. Registers the class in `PluginManager` so that `handle(object $configuration)` is called every time
   the configuration of the specified module is assembled (`ModuleManager::resolveModuleConfig()` →
   `PluginManager::apply()`). `$configuration` is a **fresh deep copy** of the module schema DTO on
   every call, so it can be freely mutated — it never "leaks" between requests or modules.
2. Only because of this attribute does `PluginManager::registerAll()`/`bootAll()` even take the
   class into account — `register()`/`boot()` are called only for classes registered via
   `#[TargetModule]`.

> **Important.** A class's `#[Filter]`/`#[Action]` methods are hooked up to `HookManager` **regardless**
> of whether `#[TargetModule]` is present — method reflection always happens. But if a plugin
> has no `#[TargetModule]`, its `register()`/`boot()` will simply never be called. So even
> if a plugin's only purpose is to register a route or block type in `boot()` and it touches no
> module configuration at all, still leave `#[TargetModule('User')]` in place as a neutral anchor (`User`
> is always the installed starter module, which is why the `nexus:make:plugin` command's default is
> exactly that). This is visible in the real plugins `GraphQLPlugin` and `BlockTypesPlugin` — both carry
> `#[TargetModule('User')]` with an empty `handle()`, with all the actual work happening in `boot()`.

## `#[Filter]` / `#[Action]` and the `nexus_filter()` / `nexus_action()` helpers

This is a named, priority-ordered extension mechanism in the WordPress style
(`apply_filters()`/`do_action()`) — any module or plugin can hook into an extension
point without editing the code that declares it.

- **Filter** — transforms a value and **must return** the (possibly modified) value.
- **Action** — performs a side effect; the returned value is ignored.

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

`#[Action]` has an identical signature. Both attributes can be stacked on a single method repeatedly (for
several hooks); the method must be **public** — `PluginManager::discoverClass()` only reflects
`ReflectionMethod::IS_PUBLIC`. A lower `priority` runs earlier (default is 10, as in
WordPress).

Calling a hook from module or package code is done through the global helpers:

```php
nexus_filter(string $hook, mixed $value, mixed ...$args): mixed;   // returns the transformed value
nexus_action(string $hook, mixed ...$args): void;
```

### Example: registering and invoking

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

The call site (somewhere in the package — this is literally where the hook actually fires for any module):

```php
$rules = nexus_filter('nexus.validation.rules', $rules, $moduleConfig, $action);
nexus_action('nexus.field_types.register', app(FieldTypeRegistry::class));
```

Note that a hook is **not** scoped to a single module: all registered listeners for
`nexus.validation.rules` are called for every module. If you need behavior scoped to a
specific module, check for it inside the method itself (`if ($moduleConfig->name !== 'demo') return $rules;`),
as in the example above.

A `[ClassName, 'method']` callback is resolved through the container **lazily**, at the moment the hook fires —
the plugin class is not instantiated unless one of its hooks actually fired.

## `#[AttachField]` / `#[AttachColumn]` / `#[AttachFilter]` — attaching a field/column/filter to a module you don't own

Declarative sugar over `handle()` for the most common scenario: a specialized module (e.g.,
`Review`) adds a single admin field/column/filter to a base module (e.g., `Product`) that it doesn't
own — without the `Product` file ever mentioning `Review` at all.

The attributes are placed on a public marker method of a class that also carries `#[TargetModule]`. **The
method body is never called** — only the attributes are read (same as with `#[Filter]`/`#[Action]`):

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

Parameters:

| Attribute | Constructor parameters |
| --- | --- |
| `AttachField` | `name`, `type`, `section = 'default'`, `label = null`, `isRequired = false`, `order = 0`, `apiExpose = false`, `permission = null` |
| `AttachColumn` | `name`, `label = null`, `sortable = false`, `tableDefault = true`, `order = 0`, `permission = null` |
| `AttachFilter` | `name`, `label = null`, `type = 'search'`, `permission = null` |

Important nuances:

- **`#[AttachField]` + `#[Relation]` only makes the field visible in the `Product` form** — the actual Eloquent
  relation must exist separately, declared on the `Review` side via `#[AttachRelation]` (see
  below). Both halves are needed for the `relationManager` field to actually work — `AttachField`
  alone won't make `$product->reviews()` resolve.
- **`label` must be in the form `'ownNamespace::translate.key'`**, not a bare string. The
  `_label.blade.php`/`module-table.blade.php` partials resolve a bare label against the label file of the
  **target** module (`Product`, which you can't touch) — the `'::'` form bypasses this and takes the
  translation from an arbitrary namespace, so put the actual translation in `resources/lang/{locale}/translate.php`
  of your own module (`Review`).
- **`permission`, if set, independently gates the attachment per viewer**, separately from
  whatever permission `Product`'s own edit/view action already requires. A record the current viewer isn't
  allowed to see doesn't make it into the configuration at all (it's not rendered hidden) — this is exactly what
  prevents a paid/optional module's data from leaking to every editor of the free base module. Leave it
  `null` to inherit the target module's gate (the default, backward-compatible behavior).
- `#[AttachField(apiExpose: true)]` mirrors `#[Field(apiExpose:)]` — it exposes the attached
  field over REST/GraphQL the same way as the module's own field.
- The value of `#[AttachColumn]`/`#[AttachFilter]` still has to resolve on the target model as a
  regular column/filter — these attributes only make the entry visible, they don't create the data.
  A computed column (e.g., `_count`) needs a matching `#[AttachScope]` with `withCount(...)`.
- All three attributes are processed by `PluginManager` together with `#[Filter]`/`#[Action]`. Disabling
  the plugin (or the absence of the `Review` module) removes the attachment on the next request without failures —
  fail-quiet, never a 500.

## `#[AttachRelation]` / `#[AttachScope]` — resolving relations and scopes between modules

These attributes are placed not on a plugin, but on public **static** methods in a module's
`Relations/` folder (e.g., `app/Nexus/Modules/Review/Relations/ProductRelations.php`) and build on top of
the standard Eloquent mechanisms — `Model::resolveRelationUsing()` and `Model::addGlobalScope()`
respectively; there's nothing Nexus-specific here.

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

`AttachScope::$name` defaults to the method name if not set explicitly. The dependency
direction here is intentionally reversed: the specialized module (`Review`) knows about the base
module (`ShopProduct`), not the other way around — `ShopProduct` never finds out that `Review` even exists.

`#[AttachRelation]` is precisely the piece needed for the `relationManager` field from
`#[AttachField]` above to actually resolve: `#[AttachField]`+`#[Relation]` only displays the field in the
form, `#[AttachRelation]` is what makes `$product->reviews` an actual working relation.

## Event catalog and their hook counterparts

Almost every extension point in the package fires **twice at the same spot**: first a
real Laravel event, immediately followed by the corresponding `nexus_filter()`/`nexus_action()` with
identical data. This is intentional duplication, not one replacing the other: a module's own
`Listeners/` class (needs neither a plugin nor `#[TargetModule]`) is the natural choice for a
specific module's own reaction, while a plugin's `#[Filter]`/`#[Action]` is for reusable,
cross-module, independently toggleable logic.

An event named `*ing`/`*Building`/`*Preparing`/`*Resolving` mutates a property **by
reference** (`&$data`, `&$rules`, `&$result`, ...) — write directly into that property; don't
`return` anything from the handler. Throwing an exception from such an event interrupts the operation via a
regular exception (there's no separate "cancel" flag in the package). A past-tense event (`EntityCreated`,
`ModuleInstalled`, ...) is a pure notification of a fact — nothing to mutate.

### Entity lifecycle

`$moduleConfig` is the target module's schema DTO (`DefaultModuleConfigurationDto`).

| Moment | Event class | Hook (type) | Data |
| --- | --- | --- | --- |
| Before creation (including duplication) | `EntityCreating` | `nexus.entity.creating` (filter) | `Model $model, array &$data, $moduleConfig` |
| After creation | `EntityCreated` | `nexus.entity.created` (action) | `Model $model, $moduleConfig` |
| Before update | `EntityUpdating` | `nexus.entity.updating` (filter) | `Model $model, array &$data, array $oldData, $moduleConfig` |
| After update | `EntityUpdated` | `nexus.entity.updated` (action) | `Model $model, $moduleConfig` |
| Before deletion | `EntityDeleting` | `nexus.entity.deleting` (action) | `Model $model, $moduleConfig` |
| After deletion | `EntityDeleted` | `nexus.entity.deleted` (action) | `Model $model, $moduleConfig` |
| Before restoration | `EntityRestoring` | `nexus.entity.restoring` (action) | `Model $model, $moduleConfig` |
| After restoration | `EntityRestored` | `nexus.entity.restored` (action) | `Model $model, $moduleConfig` |
| Any table/group action completed (delete/restore/duplicate/custom) | `ModuleActionExecuted` | `nexus.module.action_executed` (action) | `string $moduleName, string $actionName, ?string $id, ?array $ids` — read-only actions (index/edit/create/view) are not included |
| A bulk action over a selection is about to run | `BulkActionExecuting` | `nexus.bulk_action.executing` (filter) | `string $moduleName, string $actionName, array &$ids` — part of the ids can be removed (partial veto) |

### Building the module's admin form/table

A general, imperative "relative" of `#[AttachField]`/`#[AttachColumn]` — lets you add a field/
column/tab without touching the module's file.

| Moment | Event class | Hook (type) | Data |
| --- | --- | --- | --- |
| Form configuration assembled, before rendering | `AdminFormBuilding` | `nexus.form.building` (action) | `moduleName, config, ?Model $model, ?array $liveData` — mutate `$event->config` directly |
| Form fields finalized | `FormFieldsPrepared` | `nexus.form.fields_prepared` (filter) | `moduleName, array &$fields` |
| Table configuration assembled, before rendering | `AdminTableBuilding` | `nexus.table.building` (action) | `moduleName, config` — mutate `$event->config` (columns/filters/actions) |
| Table rows retrieved | `TableDataPrepared` | `nexus.table.data_prepared` (filter) | `moduleName, array &$data` |

### Validation

| Moment | Event class | Hook (type) | Data |
| --- | --- | --- | --- |
| A `FormRequest` is about to be validated | `PreparingForValidation` | `nexus.validation.preparing` (action) | `FormRequest $request` |
| Rules assembled (also fires for a module's own handwritten `AdminStoreRequest`/`AdminUpdateRequest` — via `NexusFormRequest::withValidator()`) | `GatheringValidationRules` | `nexus.validation.rules` (filter) | `$moduleConfig, array &$rules, string $action` |

### API, import/export

| Moment | Event class | Hook (type) | Data |
| --- | --- | --- | --- |
| The general `NexusResource` serializes a model (skipped entirely if the module has its own `{Model}Resource`) | `ApiResourceBuilding` | `nexus.api.resource` (filter) | `Model $resource, array &$data` |
| Each row of a CSV export | `ExportRowBuilding` | `nexus.export.row` (filter) | `Model $model, array &$row, string $moduleName` |
| Each row of a CSV import, before mass assignment (filtered by fillable regardless) | `ImportRowBuilding` | `nexus.import.row` (filter) | `array &$data, $moduleConfig` |

### Permissions, menu, dashboard

| Moment | Event class | Hook (type) | Data |
| --- | --- | --- | --- |
| A permission decision has just been computed | `PermissionChecking` | `nexus.permission.check` (filter) | `string $action, ?Module $module, string $place, bool &$result` |
| Side menu assembled | `SidebarMenuBuilding` | `nexus.menu.sidebar` (filter) | `array &$menu` — an array of `MenuConfigDto`; you can add an entry not tied to any module |
| Dashboard layout resolved (cascade: own row → is_default row → config already applied) | `DashboardLayoutResolving` | `nexus.dashboard.layout` (filter) | `?Authenticatable $user, array &$layout` |

### Media, module discovery/lifecycle, search, notifications

| Moment | Event class | Hook (type) | Data |
| --- | --- | --- | --- |
| Before attaching a media file (throwing an exception rejects the upload) | `MediaAttaching` | `nexus.media.attaching` (action) | `Model $model, UploadedFile $file, string $collection` |
| After attaching a genuinely new file (not a sha256 dedup) | `MediaAttached` | `nexus.media.attached` (action) | `Model $model, MediaItemDto $item, string $collection` |
| Module list assembled, after the Modules/UserModules filesystem scan | `ModuleDiscoveryCompleted` | `nexus.module.discovery` (filter) | `Collection &$modules` — add a module without a real directory, or hide an existing one |
| Module just installed / uninstalled | `ModuleInstalled` / `ModuleUninstalled` | `nexus.module.installed` / `nexus.module.uninstalled` (action) | `string $moduleName` |
| Global search results assembled | `GlobalSearchCompleted` | `nexus.search.results` (filter) | `array &$results, string $term` — add a results group outside the modules |
| Admin notification-bell payload assembled | `NotificationDataBuilding` | `nexus.notification.data` (filter) | `Notification $notification, $notifiable, array &$data` |

### Field types, widgets (boot-time / output)

| Moment | Event class | Hook (type) | Data |
| --- | --- | --- | --- |
| Field types registration at boot (the third and final registration point) | `FieldTypesRegistering` | `nexus.field_types.register` (action) | `FieldTypeRegistry $registry` |
| A widget's `data` or rendered `html` has been resolved, **before** `WidgetOutputCache` caching | `WidgetOutputResolving` | `widget.data.{key}` / `widget.html.{key}` (filter) | `string $widgetKey, string $kind, mixed &$output, WidgetContext $context` |

Every event class from the tables above lives in `packages/nodex/nexus/src/Events/` with a full docblock
(rationale + a ready-made listener example) — before guessing at the payload shape,
open the class itself. `ModuleEvent` is an older, untyped event kept for backward
compatibility; for new code, choose a concrete typed event from the tables above.

## Scaffolding commands

```bash
php artisan nexus:make:plugin {name} --module={module}
```

Creates `app/Nexus/Plugins/{name}/{name}Plugin.php` with a skeleton containing `#[TargetModule]`,
`register()`/`boot()`/`handle()`, and examples of `#[Filter]`/`#[Action]`. No additional
registration is needed — the plugin is picked up automatically on the next request.

```bash
php artisan nexus:make:filter {module}
```

This is a **separate** command — it's unrelated to the `#[Filter]` attribute/hooks above; it creates
`app/Nexus/Modules/{module}/Filters/ModuleFilterHandler.php`, a class that overrides
`FilterHandler::filter()` for that module's *admin table*. It's needed when
`#[AttachFilter]` (or the module's own `#[TableFilter]`) declares a `type` other than
the default `'search'` — `'search'` is already implemented by the base `FilterHandler` (LIKE across all
table columns) and needs no extra code, while a custom `type` has to be handled here:

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

The class is resolved automatically by the module's name — no manual registration needed, but every
custom filter still has to be declared in the module's `TableConfigDto->filters` (via
`#[TableFilter]` or `#[AttachFilter]`), otherwise it simply won't show up in the list UI.

## Plugin discovery and boot timing

`PluginManager::autoDiscover()` scans `app_path('Nexus/Plugins')` one level deep
(`{Plugin}/*.php`), as well as individual `.php` files directly inside `Nexus/Plugins/` (for simple
single-file plugins). To disable a plugin without deleting the file:

```php
// config/nexus.php
'plugins' => [
    'disabled' => [
        \App\Nexus\Plugins\Foo\FooPlugin::class,
    ],
],
```

The `plugins` key doesn't exist in the package's default config file out of the box — add the array
yourself if needed; `config('nexus.plugins.disabled', [])` falls back to an empty array either way if the key is missing.

> **Important: `boot()`, not `register()`.** The package itself calls `PluginManager::autoDiscover()`
> and `registerAll()` from `NexusServiceProvider::boot()`, **not** from `register()` — and there's a
> specific reason for this: `PluginManager::isPluginEnabled()` (the plugin on/off toggle
> in the admin panel, the `nexus_plugins` table) reads from the DB, and the Eloquent connection resolver isn't
> ready yet during `register()` — every provider's `register()` in Laravel runs before any
> provider's `boot()`, including the Eloquent provider's own. `bootAll()` (calling `boot()` on already
> registered plugins) is accordingly called later, in the package's `boot()`, under the same
> `app.debug` gate. **If you write your own code that hits the DB during
> discovery/registration of your own extensions (not just Nexus plugins), run it from your own
> `ServiceProvider`'s `boot()`, not from `register()`.** Violating this order doesn't throw an exception —
> the DB check simply fails silently (typically fail-open, i.e., appearing as if "everything is enabled"),
> which is hard to catch later.

An exception thrown during discovery/`register()`/`boot()` of a single plugin only propagates further
if `app.debug === true`; otherwise it is logged via `report()` and swallowed — meaning one
broken plugin won't take down the entire admin panel in production. This is not a reason to ignore the error — check the
logs; silence still doesn't mean success.

## Ready-made example

`app/Nexus/Plugins/ConfirmFixtureNote/ConfirmFixtureNotePlugin.php` — a working example of
`#[AttachField]`/`#[AttachColumn]`/`#[AttachFilter]` (adds a "notes" relationManager field,
a "notes_count" counter, and a "notes_search" search filter to the `ConfirmFixture` module, which it
doesn't own), paired with the data layer in `Relations/ConfirmFixtureRelations.php` of that same module.
Covered by the test `tests/Feature/Nexus/AttachFieldColumnTest.php`.

`app/Nexus/Plugins/Example/ExamplePlugin.php` — a working example of the full cycle
discover → registerAll/bootAll → apply(): `handle()`, which mutates a menu item's label, `#[Filter]`
on `nexus.validation.rules` scoped to the `Demo` module, and `#[Action]`, which registers a field-type
alias. Covered by the test `tests/Feature/Nexus/PluginSystemTest.php`.

`app/Nexus/Plugins/GraphQL/GraphQLPlugin.php` and `app/Nexus/Plugins/BlockTypes/BlockTypesPlugin.php`
— examples of anchor plugins (`#[TargetModule('User')]` with an empty `handle()`), all of whose work
happens in `boot()` (registering the GraphQL API route and editor block types, respectively).
