# Nexus Artisan Command Reference

The full list of `artisan` commands registered by the `nodex/nexus` package
(`src/commands/`). For a general overview of installing and updating the package,
see `Instalation.md` — here, each command is described individually, with its exact
signature and an explanation of what it actually does under the hood.

## Installation and updates

### `nexus:install`

```
php artisan nexus:install
```

Initial deployment of the package into the application. Creates the
`app/Nexus/Modules` and `app/Nexus/Plugins` directories if they don't already exist, then
sequentially calls `nexus:resource:publish`, the standard Laravel
`notifications:table` command (the admin panel header unconditionally calls
`unreadNotifications()`, so the notifications table is needed even without a single
custom Nexus module; `notifications:table` itself won't re-publish the migration
if it already exists), `migrate`, `nexus:permission:init`, and
`nexus:module:install` (with no argument — installs all already-published
modules). Publishing the starter modules (`nexus:default_module:publish`) is intentionally
excluded from this chain — it's commented out in the command's code, so
`Auth`/`User`/`Permission`/`Role` need to be published separately before or after
`nexus:install`. This command is run once, right after
`composer require nodex/nexus`.

### `nexus:update`

```
php artisan nexus:update
```

Updates an already-installed package to a new version: runs
`composer update nodex/nexus` via `exec()`, then calls
`nexus:resource:publish` to re-publish the package's config/resources/JS/translations
over the application. It doesn't touch modules in `app/Nexus/Modules` — they've already
been copied into the application and are updated just like regular project code.

### `nexus:resource:publish`

```
php artisan nexus:resource:publish
```

A wrapper around four `vendor:publish` calls (tags `nexus-config`,
`nexus-resources-publish`, `nexus-js`, `nexus-lang`) — publishes the package's config,
resources, frontend scripts, and language files into the application. It's called
automatically from `nexus:install` and `nexus:update`, but can also be run
manually if you just need to "pull in" fresh package resources without a full
install/update cycle.

### `nexus:module:install`

```
php artisan nexus:module:install {name?}
```

Registers (installs) a module via `ModuleManager`. With the `{name}` argument
it installs a specific module (the name is automatically converted with `Str::ucfirst`);
without an argument, it walks through all modules returned by
`ModuleManager::getModules()` and installs them one by one. It's run after
`nexus:make:module` (for a new module) or after
`nexus:default_module:publish` (for starter modules), to make the module
visible to the system — unlike publishing files, it's this command that actually
"turns on" the module.

### `nexus:default_module:publish`

```
php artisan nexus:default_module:publish 
                        {--module= : Module name or comma-separated list}
                        {--force : Overwrite existing modules}
```

Copies the package's starter modules (`Auth`, `User`, `Permission`, `Role` —
from `src/Modules`) into `app/Nexus/Modules`, rewriting the
`Nodex\Nexus\Modules\{Name}` namespace to `App\Nexus\Modules\{Name}` in the
copied files. `--module=Auth,User` restricts which modules get published; without the
option, all of them are published. If a module's target directory already exists, publishing
is skipped with a warning — `--force` forcibly deletes and overwrites
it (careful: if the module's migration has already run, a repeated `--force`
re-stamps the file and Laravel will attempt to apply the "new" migration again —
more detail on this in `Instalation.md`). The command also shifts the module's
migration timestamps to the current publish time, so they're guaranteed to
sort after whatever is already in `database/migrations` (including
the just-published `spatie/laravel-permission` migrations), and appends the
module name to the migration file name to avoid collisions between identically named
migrations from different modules. Separately, if `User` is among the modules being
published, it copies `src/AppStubs/User.php.stub` into `app/Models/User.php` (the same
"don't overwrite without `--force`" logic) — Laravel always expects the
auth model to live at exactly that path, so it can't live inside a module.

## Code generation / scaffolding

### `nexus:make:module`

```
php artisan nexus:make:module {name}
```

Creates the scaffolding for a new admin module in `app/Nexus/Modules/{Name}`, following
the package's actual conventions: a model (`Models/{Name}.php`), form requests
(`Requests/AdminStoreRequest.php`, `Requests/AdminUpdateRequest.php`), a
migration (`database/migrations/..._create_{pluralSnakeName}_table.php`),
translation files (`resources/lang/en|uk/translate.php`), and a module
documentation page (`resources/views/docs.blade.php`) — all generated from templates
in `src/commands/stubs`. Fails with an error if a module with that name already
exists (no overwriting, and there's no `--force` option for this command). After generation
it prints a hint about the next steps: add fields/relations to the model
(see `nexus:docs:field-types`), run `migrate`, and
`nexus:module:install {name}`. This is the starting point for any new
content type in the admin panel.

### `nexus:make:field`

```
php artisan nexus:make:field {name} {--module= : The module this field type belongs to}
```

Creates a custom field type renderer class (`FieldTypeRenderer`) along with a
Blade partial, under `{Module}/FieldTypes/`. The `--module` option is required —
without it the command exits with an error; if the specified module doesn't exist in
`app/Nexus/Modules`, it prints a list of available modules and also exits with an
error. If a class with that name already exists, it's an error with no overwriting.
You can start using the new type right away via
`#[Field(type: '{lowerName}', ...)]` on a model property — the command
warns that discovery is automatic, but if the project has an active module
cache (`bootstrap/cache/nexus-modules.php` exists), you also need to run
`nexus:module:cache` for the new type to be picked up.

### `nexus:make:filter`

```
php artisan nexus:make:filter {module}
```

Creates a module's filter handler — a `ModuleFilterHandler` class that
overrides `FilterHandler::filter()`, under `{Module}/Filters/`. The `{module}`
argument is required; if the module doesn't exist, it prints a list of available
modules and exits with an error, and the same happens if a handler for this
module already exists. It's auto-resolved by name (no manual
registration needed), but every filter added inside it must be separately declared in
the module's `TableConfigDto` (`FilterConfigDto`) — otherwise it won't appear in
the admin panel's list UI.

### `nexus:make:plugin`

```
php artisan nexus:make:plugin {name} {--module= : The name of the module to target}
```

Creates a plugin scaffold (`{Name}Plugin.php` in `app/Nexus/Plugins/{Name}`) —
a class with `#[TargetModule]`/`#[Filter]`/`#[Action]` attributes that extends
the behavior of an existing module without its own database table. `--module` sets
the target module (`User` by default); the name is normalized via
`Str::ucfirst`. If the plugin file already exists, it's an error with no overwriting.
The plugin is discovered automatically on the next request; the command reminds you
to remove any unneeded `#[Filter]`/`#[Action]` methods and fill in
`handle()`/`register()`/`boot()`.

### `nexus:make:widget`

```
php artisan nexus:make:widget {name}
```

Creates a dashboard widget scaffold: a `{Name}.php` class with the `#[Widget]` attribute
that implements `WidgetInterface`, and a Blade template, placed together in a single
folder `app/Nexus/Widgets/{Name}` (rather than in `resources/views/widgets/`) —
precisely because `RendersHtml::render()` resolves the template via `View::file()`
and `__DIR__`. It's an error with no overwriting if the class already exists. After generation
it hints: the widget is discovered automatically, and for it to show up by
default on the dashboard, add its key to
`config('nexus.dashboard.default')`, or attach it manually via the "Customize"
picker in the admin panel itself.

## Module cache

### `nexus:module:cache`

```
php artisan nexus:module:cache
```

Compiles a file manifest of all modules (views/routes/translations/icons/
commands/listeners) into `bootstrap/cache/nexus-modules.php`, so that every request
doesn't scan the filesystem live. Internally it refreshes the `ModuleRegistry`
(`refresh()`), builds the manifest via `ModuleManifestCache::build()`, and
writes it to disk. It prints the path to the cache file and the number of
compiled modules. Used as a production optimization (analogous to
`config:cache`/`route:cache`) — run it after a deploy or any change to the
set of modules/plugins.

### `nexus:module:clear`

```
php artisan nexus:module:clear
```

Deletes the compiled module manifest, returning the application to live
filesystem scanning on every request. The counterpart command to
`nexus:module:cache` — run it before local development (so new
modules/fields/plugins are picked up immediately), or before re-caching
after structural changes.

## Permissions and users

### `nexus:permission:init`

```
php artisan nexus:permission:init
```

Syncs the admin panel's base permissions with the code: it walks through all
`AdminPanelPermissionEnum` cases, builds a human-readable name (`display_name.en`)
from the enum value, and runs `Permission::updateOrCreate()` for each one —
meaning it's safe to run repeatedly; new permissions get added, and existing ones
only have their `display_name` updated. It's called automatically from `nexus:install` and
from `nexus:create:superadmin`, but it's also worth running manually after
`AdminPanelPermissionEnum` has been manually extended with new values — so they
show up in the permissions table.

### `nexus:create:superadmin`

```
php artisan nexus:create:superadmin {name?} {email?} {password?} {--name=} {--email=} {--password=}
```

Creates a user with the `super-admin` role, which is assigned absolutely all
`web` guard permissions. The name/email/password can be passed as positional
arguments, as named options, or (if not passed) the command will prompt for them
interactively (`ask()`/`secret()` for the password). It first calls
`nexus:permission:init`, to make sure permissions actually exist. If a user with
that email already exists, the command just reports it and creates nothing
(it doesn't fail with an error). The `super-admin` role is created via
`firstOrCreate()` if it doesn't exist yet, and is synced with all existing
permissions (`syncPermissions()`) on every run — meaning a repeated
call will refresh the role's permission set even if the user was already created earlier.
This is the first command run right after `nexus:install`, so there's something
to log into the admin panel with.

### `nexus:api-token:issue`

```
php artisan nexus:api-token:issue {email}
```

Issues a Sanctum personal access token for an existing user by email
(the model is taken from `config('auth.providers.users.model')`, so the command isn't
hard-tied to `App\Models\User`). If no user with that email
exists, it's an error. The token is printed to the console once (`createToken('api')
->plainTextToken`) — this is a temporary tool for REST/GraphQL access,
until the admin panel has its own self-service screen for API tokens.

## Documentation

### `nexus:docs:field-types`

```
php artisan nexus:docs:field-types {--markdown= : Write the reference as a Markdown file to this path instead of printing a table}
```

Generates a complete reference of every `#[Field(type: ...)]` value the
admin panel's Livewire form can actually render: built-in types (with a description of each
— `string`, `text`, `relation`, `blockEditor`, and so on), aliases, and types
registered by modules/plugins via `FieldTypeRegistry`. Without
`--markdown` it prints a table to the console; with `--markdown={path}` it writes
that same reference to a Markdown file (this is exactly how
`docs/field-types.md` in this package was generated). The command self-verifies: it
cross-checks its hardcoded array of descriptions (`$builtIn`) against the actual partials on disk
(`resources/views/tailadmin/livewire/field_types`) and prints warnings
about types that exist on disk but aren't documented, or are documented but no longer
have a partial — so the reference doesn't silently drift out of sync with the code.
Run it after adding a new built-in field type, or just to see
which types are actually available for `#[Field(...)]`.
