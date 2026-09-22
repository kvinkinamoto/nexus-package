# Nodex\Nexus Package Architecture

This document covers the internal "plumbing" of the `nodex/nexus` package
itself (`packages/nodex/nexus`): how `NexusServiceProvider` boots the
application, how the config is structured, the module manifest cache, Blade
directives, the template system, and field-type registration. How to write a
module/plugin/widget is covered in separate documents
([modules.md](modules.md), [plugins.md](plugins.md), [widgets.md](widgets.md));
none of that is here.

The source of truth is the code itself, primarily
`src/NexusServiceProvider.php`. All statements below have been checked
against the actual files; where there is no certainty, that is stated
explicitly.

## 1. Boot sequence (`NexusServiceProvider`)

### `register()`

Order matters: later steps rely on singletons from earlier steps already
existing in the container.

1. `require_once __DIR__.'/helpers/functions.php'` — makes `nexus_filter()`/
   `nexus_action()` globally available.
2. Almost all of the package's core services are bound as `singleton()`:
   `PathManager`, `ModuleRegistry`, `ModuleManager`, `ModuleManifestCache`,
   `FormBuilder`, `ModuleServiceForAdminPanel`, `IconManager`, `HookManager`,
   `RelationRegistrar`, `ModuleDependencyChecker`, `PluginManager`,
   `AttributeSchemaReader`, `DirectTranslationService`, `FieldTypeRegistry`,
   `FieldVisibilityEvaluator`, `WidgetRegistry`, `BlockTypeRegistry`,
   `DashboardLayoutResolver`, `AdminDashboardRenderer`, `FrontWidgetRenderer`,
   `TemplateTypeResolver`, `NexusRuleCollector`.
3. `MediaLibraryInterface` is bound via `bind()` (not `singleton()`) to
   `SpatieMediaLibraryService` — the application can override this binding in
   its own provider (which loads after `NexusServiceProvider`), and the
   override simply wins, with no dedicated extension point on Nexus's side:

   ```php
   $this->app->bind(
       MediaLibraryInterface::class,
       SpatieMediaLibraryService::class,
   );
   ```
4. `SettingsProviderInterface` → `DatabaseSettingsProvider` is likewise
   `bind()`-ed (the store for `#[Setting(...)]`).
5. If the `Faker\Generator` class is available, a `FakerGenerator` singleton
   is registered with a custom `FakerImageProvider`.

`PluginManager::autoDiscover()`/`registerAll()` are **not called** in
`register()` — they were moved into `boot()` (see the "Gotchas" section
below), and a comment right in the code explains why.

### `boot()`, in the actual order of the source

1. **Plugin discovery and registration** (`PluginManager::autoDiscover()` +
   `registerAll()`), wrapped in `try/catch`: under `app.debug` the exception
   is rethrown, otherwise it's `report($e)` and silently swallowed.
2. `Model::shouldBeStrict(! $this->app->isProduction())`.
3. `mergeConfig()` — `mergeConfigFrom(config/nexus.php, 'nexus')`.
4. `publish()` — registers the publishable groups (table below).
5. `loadMigrationsFrom(__DIR__.'/database/migrations')` of the package +
   `registerDefaultApiRateLimiter()` (a default `'api'` rate limiter, only if
   the application hasn't already defined its own — otherwise `throttleApi()`
   without an app-defined `RateLimiter::for('api', ...)` would fail with
   `MissingRateLimiterException`).
6. Three Blade directives are registered: `@position`, `@nexusForm`,
   `@nexusBlocks` (section 4).
7. `registerValidationRulesFilter()` — a hook on
   `Illuminate\Contracts\Validation\Factory::resolver()`.
8. The list of modules for discovery is determined: outside the console —
   from `ModuleManifestCache::get()` (if the cache file exists), otherwise
   `ModuleRegistry::getEnabledModules()`; in the console — always **all**
   modules via `ModuleRegistry::getAllModules()` (so that `migrate:fresh` and
   similar commands see everything, regardless of `is_enabled`).
9. In sequence: `loadMigration()` → `loadView()` → `loadTranslation()` →
   `loadIcon()` → `loadRoute()` → `loadEvents()` →
   `Event::listen(ModuleInstalled::class, SendModuleInstalledNotification::class)`
   → `loadRelations()` → `loadFieldTypes()` →
   `registerBuiltInFieldTypeAliases()` → `registerBuiltInFieldTypeDefaultRules()`
   → `loadWidgets()` → `loadLivewireComponents()` (registers three Livewire
   components: `nexus-module-table`, `nexus-module-form`,
   `nexus-module-settings-form`).
10. `viewCompose()` — attaches a `View::composer()` to
    `nexus::{template}.layouts.adminpanel` (sidebar menu +
    `moduleMissingDependencies`), plus each module's own `$config->composers`.
11. In the console — `registerSeeders()`.
12. `runCommand()` — registers the package's and modules' artisan commands.
13. **Plugin boot** (`PluginManager::bootAll()`), the same `try/catch` with
    the `app.debug` gate as in step 1.
14. The final field-type registration point: the `FieldTypesRegistering`
    event + `nexus_action('nexus.field_types.register', $fieldTypeRegistry)`.

```php
// NexusServiceProvider::boot(), excerpt — the three current field-type registration points
$this->loadFieldTypes();                       // 2: per-module FieldTypes/*.php
$this->registerBuiltInFieldTypeAliases();
$this->registerBuiltInFieldTypeDefaultRules();
...
$fieldTypeRegistry = $this->app->make(FieldTypeRegistry::class);
event(new FieldTypesRegistering($fieldTypeRegistry));                 // 3a
nexus_action('nexus.field_types.register', $fieldTypeRegistry);       // 3b
```

## 2. Config: `config/nexus.php`

### Keys in the package file (`packages/nodex/nexus/src/config/nexus.php`)

| Key | Purpose |
| --- | --- |
| `template` | The active admin theme (`env('ZENTARA_TEMPLATE', 'tailadmin')`) — section 5. |
| `admin_prefix` | Prefix for admin routes (`env('ADMIN_PREFIX', 'admin')`). |
| `admin_middleware` | The admin middleware stack: `['web', 'auth', NexusAdminMiddleware::class]` + a commented-out example for app-specific middleware (such as localization). |
| `api_prefix` | Prefix for API routes (`env('API_PREFIX', 'api')`). |
| `api_middleware` | The API middleware stack: `['api']`. |
| `table.pagination.per_page_options` / `default_per_page` | Pagination options in module tables. |
| `permissions.generate_default` | Whether to generate default permissions for a module. |
| `toast.enabled` / `toast.delay` | Toast-notification behavior in the admin panel. |
| `widget_template_map` | A `route name → template type` map for `@position`/front-end widgets (see `TemplateTypeResolver`). |
| `dashboard.default` | An ordered list of widget keys (`#[Widget(name:)]`) for the dashboard on a fresh install with no rows yet in `nexus_dashboard_layouts`. |
| `media_library.enabled` | Enables/disables `#[Field(type: 'gallery')]` (shows a stub message instead of the field when `false`). |
| `plugins.disabled` | A list of plugin FQCNs that `PluginManager::autoDiscover()` should skip. |

The `nexus.graphql_middleware` and `nexus.graphql_prefix` keys are
**deliberately absent** from the package file — GraphQL is a paid app-level
plugin (`app/Nexus/Plugins/GraphQL`), not part of this repository (see
`../README.md`), so these keys live only in the application's root
`config/nexus.php`.

### Duplicated config file (root vs. package)

`packages/nodex/nexus/src/config/nexus.php` (the package defaults) and the
published copy `config/nexus.php` at the application root are two separate
files, and `mergeConfigFrom()` in `boot()` **only fills in keys that are
missing from the root file**; it never overwrites a key that's already
there. In other words, for any key present in both files, the value that
actually takes effect is the one from the root file — you can verify this
with:

```bash
php artisan config:show nexus.dashboard
```

As of now, both files have been checked and synchronized for their shared
keys (`dashboard.default`, `media_library`, and `plugins.disabled` have been
brought to matching values; the package's `admin_middleware` has a
commented-out example in place of the active app-specific
`SetAdminLocale::class`, which remains only in the root file).
`graphql_prefix`/`graphql_middleware` live exclusively in the root file —
deliberately, not as a sync oversight.

⚠️ **Rule for maintainers**: if you add or change any `nexus.*` key that
isn't app-specific, edit **both** files
(`packages/nodex/nexus/src/config/nexus.php` and the root `config/nexus.php`),
and verify which value is actually active via `config:show`. A new key that
isn't yet in the root file will be picked up normally through
`mergeConfigFrom()` — which is exactly why this trap is easy to miss the
first time: everything works, as long as the key is new.

## 3. Module manifest cache (`bootstrap/cache/nexus-modules.php`)

`ModuleManifestCache` (`src/Services/ModuleManifestCache.php`) compiles the
result of each module's on-disk scan (views/routes/translations/icons/
commands/listeners/relations/scopes/fieldTypes/widgets) into a single PHP
array written to `bootstrap/cache/nexus-modules.php`:

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

Why it exists: without the cache, `NexusServiceProvider::boot()` performs
`is_dir`/`is_file` checks, reads directories, and reflects classes for every
enabled module on **every** HTTP request — this is purely a performance
optimization.

Key properties:

- It's only read outside the console (`! $this->app->runningInConsole()`) —
  commands (`migrate`, tests, etc.) always scan the disk live, so they see
  newly added modules without needing to recompile the cache.
- If the cache file is missing, `ModuleManifestCache::get()` returns `null`,
  and `NexusServiceProvider` silently falls back to live scanning; behavior
  doesn't change.
- The list of enabled modules from the cache is still filtered through one
  live SQL query against `nexus_modules` (`enabledModulesFromManifest()`),
  because "enabled/disabled" is a toggle in the DB, not a filesystem
  structure.

Managed via commands:

```bash
php artisan nexus:module:cache   # compile bootstrap/cache/nexus-modules.php
php artisan nexus:module:clear   # delete it, fall back to live scanning
```

If something newly added under `app/Nexus/**` (a module, field type, widget,
listener, translation file) isn't showing up, the first thing to check is
whether the cache file exists.

## 4. Blade directives

All three are registered directly in `NexusServiceProvider::boot()` and
share one principle: **they never throw an error over missing data**, they
just silently render nothing.

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

Renders all active `WidgetAssignment`s for the given position via
`FrontWidgetRenderer`. If `templateType` isn't passed, it's determined via
`TemplateTypeResolver::resolve()` (route parameter → route default →
`config('nexus.widget_template_map')` by route name → `'default'`).

### `@nexusForm('contact-slug')`

```php
Blade::directive('nexusForm', function (string $expression) {
    return "<?php \$__nexusForm = \Nodex\Nexus\Modules\Form\Models\Form::query()->where('slug', {$expression})->where('is_active', true)->first(); if (\$__nexusForm) { echo view('form::public.form', ['form' => \$__nexusForm])->render(); } ?>";
});
```

Inserts a public submission form (the `Form` module) by slug. An unknown or
inactive slug simply renders nothing.

### `@nexusBlocks($page->blocks)`

```php
Blade::directive('nexusBlocks', function (string $expression) {
    return "<?php foreach (({$expression}) as \$__nexusBlock) { \$__nexusBlockView = 'nexus::public.block_types.' . \$__nexusBlock->type; if (\Illuminate\Support\Facades\View::exists(\$__nexusBlockView)) { echo view(\$__nexusBlockView, ['block' => \$__nexusBlock, 'data' => (object) (\$__nexusBlock->data ?? [])])->render(); } } ?>";
});
```

Renders an ordered collection of blocks (e.g. `PageBlock`), resolving
`nexus::public.block_types.{type}` for each one via `View::exists()`. A block
of an unknown type is skipped — the rest of the page's blocks still render.

## 5. Template system (tailadmin)

The active theme is determined by the `config('nexus.template')` key
(`env('ZENTARA_TEMPLATE', 'tailadmin')`). The value is substituted into the
view path literally in dozens of places in the package, for example:

```php
// Http/Controllers/NexusController.php
return view('nexus::'.config('nexus.template').'.pages.dashboard', [...]);

// Livewire/ModuleTable.php
return view('nexus::'.config('nexus.template').'.livewire.module-table', [...]);
```

`viewCompose()` in `NexusServiceProvider` is also tied specifically to
`nexus::{template}.layouts.adminpanel` — a new theme must have this file in
order to receive `menus`/`moduleMissingDependencies`.

**Historical note**: on disk there is only one complete theme with Blade
views — `src/resources/views/tailadmin/**` (alongside shared `components/`,
`partials/`, `public/`). A comment in the config itself hints at the
history:

```php
'template' => env('ZENTARA_TEMPLATE', 'tailadmin'), // tailadmin (nexus theme archived, see resources/views/nexus/ removed in Stage 2)
```

There used to also be a `nexus` theme, which was archived at "Stage 2" and
removed from `resources/views/nexus/`. The `adminlte` theme went down the
same path and, as of the first pass of this documentation, remained a dead
artifact: no Blade layer existed for it, yet
`src/resources/publish/adminlte/**` — 99 MB of static CSS/JS/img for the
classic AdminLTE theme — was still being published into `public/`, even
though there was nothing to render that theme with
(`ZENTARA_TEMPLATE=adminlte` would break rendering on every screen). The
directory has been **removed entirely**; two vendor JS/CSS files from it that
the `tailadmin` theme actually used
(`plugins/dropzone/dropzone.js` — drag-and-drop for image fields,
`plugins/jquery-colorbox/example1/colorbox.css` — login-page styles) were
moved into `src/resources/publish/packages/{dropzone,jquery-colorbox}` and
the references to them in views were updated. `config('nexus.template')`
now effectively supports only `tailadmin`; adding another theme will again
require its own `resources/views/{name}/layouts/adminpanel.blade.php` tree
and the rest of the files following the same convention.

## 6. Field-type registration (`FieldTypeRegistry`)

`FieldTypeRegistry` (`src/Services/FieldTypeRegistry.php`) is the single
resolution point for how `#[Field(type: ...)]` gets rendered, shared by both
dispatchers (the legacy `templates/sections/_field.blade.php` and the
Livewire `livewire/field_types/dispatch.blade.php`). The resolution order
(from the class's docblock) is the same for both:

1. `{module}::admin.field_types.{type}` (legacy) or
   `{module}::admin.livewire_field_types.{type}` (Livewire) — the module's
   own override, always wins, checked by the dispatcher itself before it
   even consults the registry.
2. This registry — `registerView()` / `registerClass()` / `registerCallback()`.
3. The built-in view at the conventional path for the type
   (`.../templates.field_types.{type}` or `.../livewire.field_types.{type}`)
   — resolved via `View::exists()`, meaning a built-in type doesn't need to
   be registered in this class at all, a file at the conventional path is
   enough.
4. `.../field_types.unknown` (legacy) or `.../field_types/unsupported`
   (Livewire) — a placeholder banner, if none of the above matched.

### Three registration points, and when each one happens

Confirmed in `NexusServiceProvider::boot()`:

1. **`loadFieldTypes()`** — scans the `FieldTypes/` folder of every enabled
   module (`ModuleManifestCache::discoverFieldTypes()` or the live version of
   the same scan) and registers every discovered renderer class via
   `$registry->registerClass($fieldType['type'], $fieldType['class'])`. The
   type is registered as `camelCase(file name)`.
2. **The `FieldTypesRegistering` event** — the final step of `boot()`,
   already after `loadFieldTypes()` and after the boot phase of all plugins.
3. **The `nexus_action('nexus.field_types.register', $fieldTypeRegistry)`
   action** — right after the event, the same registry, with no need to
   create a plugin class; this is usually the path plugins
   `#[Action(hook: 'nexus.field_types.register')]` use to register/override
   types.

Both of the last two points run **at the very end of `boot()`** — that is,
after all per-module `FieldTypes/` folders have been loaded (step 1) and
after `PluginManager::bootAll()`. This gives plugins the ability to override
a type registered by a module.

`registerBuiltInFieldTypeAliases()` (aliases `editor→text`, `date→birthday`)
and `registerBuiltInFieldTypeDefaultRules()` (base validation rules by field
type, e.g. `'email' => ['email']`) run between step 1 and steps 2–3 — they
aren't a separate "type registration point" in the sense of the
`FieldTypeRegistry` docblock, but rather they augment the already-registered
types with aliases and default validation rules.

### Is there a DB trap here, like with plugins?

There's no explicit sign in the code that `FieldTypeRegistry`/
`loadFieldTypes()` need the DB — type registration works purely off the
filesystem and class reflection (`class_exists`, `is_subclass_of`); no calls
to Eloquent or `DB::` were found here. Unlike
`PluginManager::autoDiscover()` (section 7), field-type registration has no
known DB-availability trap.

## 7. Gotchas for maintainers

### 7.1. `register()` vs. `boot()` and DB availability — `PluginManager`

The best-documented and most important trap, right in the provider file
itself. `PluginManager::autoDiscover()`/`registerAll()` are called from
`boot()`, not `register()` — deliberately, with a detailed explanation right
in the code:

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

In other words: `register()` for every service provider (including
Laravel's own `DatabaseServiceProvider`) runs **before** `boot()` for any of
them — so a DB call from `register()` is guaranteed to fail with
`Call to a member function connection() on null`. If you're planning to add
another DB-dependent discovery mechanism (say, another
enabled/disabled check against a table), run it from `boot()`, like
`PluginManager` does, and not from `register()`.

A second layer of the same trap: a plugin error during `register()`/`boot()`
doesn't silently crash the application in production —

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

— meaning that without `app.debug=true`, a broken plugin gets swallowed by
`report()`, and you can only find out from the logs, not from a visible
crash in the admin panel.

### 7.2. Duplicated config file (root vs. package)

See section 2 in full: editing
`packages/nodex/nexus/src/config/nexus.php` by itself changes nothing for a
key that already exists in the root `config/nexus.php` — you need to edit
both files and verify the active value via
`php artisan config:show nexus.<key>`.

### 7.3. The module manifest cache freezes everything at once

`bootstrap/cache/nexus-modules.php` freezes discovery for
views/routes/translations/icons/commands/listeners/relations/scopes/
fieldTypes/widgets of all modules at once — not just for one subsystem.
After any structural change under `app/Nexus/**` (a new module, a new field
type, a new listener, etc.) — run `php artisan nexus:module:clear`, if the
cache is being used at all in the current environment.

### 7.4. `adminlte` has been removed — `config('nexus.template')` de facto supports only `tailadmin`

See section 5: the `adminlte` theme was a dead artifact (no Blade view tree)
and has been removed entirely, along with 99 MB of unused static assets.
`config('nexus.template')` still formally accepts any string — another theme
without its own `resources/views/{name}/layouts/adminpanel.blade.php` will
produce "view not found" errors, just as `adminlte` did before.
