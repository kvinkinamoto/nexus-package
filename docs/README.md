# Documentation for the `nodex/nexus` package

Nexus is a Laravel package for rapidly building an admin panel: content types
("modules") are described with PHP 8 attributes directly on the Eloquent model, and the package
assembles the form, list table, validation, and (when needed) an API resource from them.
Other modules can be extended without your own database table via plugins; widgets are
surfaced on the dashboard; any module/plugin can declare its own
settings without a migration.

This page is the entry point into the documentation. Package installation is described
separately in [`../Instalation.md`](../Instalation.md); licensing terms are in
[`../README.md`](../README.md). Additional modules can be found on the project website:
[https://www.nexus-cms.shop/](https://www.nexus-cms.shop/).

If you're only using the free modules, consider supporting ongoing development with a donation:
[send.monobank.ua/jar/2V1YcJMoCr](https://send.monobank.ua/jar/2V1YcJMoCr).

## Sections

| Document | What it covers |
| --- | --- |
| [modules.md](modules.md) | The module system — `#[Module]`, `#[Field]`, `#[Column]`, `#[Section]`, `#[Relation]`, `#[RepeaterField]`, `#[Permission]`, `#[Requests]`, validation, `nexus:make:module` scaffolding, the module manifest cache. |
| [field-types.md](field-types.md) | Reference of all `#[Field(type: ...)]` values. **Auto-generated** by the `nexus:docs:field-types --markdown=docs/field-types.md` command — do not edit manually, edit `FieldTypesDocsCommand::$builtIn` instead. |
| [plugins.md](plugins.md) | Plugins and hooks — `#[TargetModule]`, `#[Filter]`/`#[Action]` (`nexus_filter()`/`nexus_action()`), `#[AttachField]`/`#[AttachColumn]`/`#[AttachFilter]`, `#[AttachRelation]`/`#[AttachScope]`, the full `Events/**` event catalog, the plugin auto-discovery boot-timing trap. |
| [widgets.md](widgets.md) | Dashboard widgets — `#[Widget]`, the `WidgetInterface`/`ProvidesMetric`/`RendersHtml`/`ApiSerializable` contracts, `nexus:make:widget`, output caching, `@position()`. |
| [settings.md](settings.md) | The settings registry — `#[Setting]`, storage in `nexus_module_settings`, the "All Settings" page, reading/writing via `SettingsBuilder`. |
| [table-features.md](table-features.md) | The admin table — `#[TableAction]`/`#[TableGroupAction]` (bulk-action side panel), `#[TableFilter]`/`#[TableLens]`, `#[TableImport]`/export, the global search fallback, per-user column visibility. |
| [menu-and-urls.md](menu-and-urls.md) | URL and menu resolvers — `#[Module(menuResolver:)]`, `UrlResolverInterface`, `RelatedEntityFieldService`/`RelatedEntityField`, the `SidebarMenuBuilding` event; a walkthrough of the `Menu`/`Redirect`/`Sitemap` modules as a usage example. |
| [artisan-commands.md](artisan-commands.md) | A complete reference of every `nexus:*` artisan command in the package, with exact signatures. |
| [architecture.md](architecture.md) | The package's internal "plumbing" — the `NexusServiceProvider::register()`/`boot()` sequence, config, the module manifest cache, Blade directives, the template system (tailadmin/adminlte), field-type registration. |

## Known issues found during documentation

Everything found during the first documentation pass has already been fixed.

### Fixed after the first documentation pass

- **`UserMenuResolver`** (the `#[Module(menuResolver:)]` of the starter module
  `User`, `src/AppStubs/User.php.stub` / the published `app/Models/User.php`)
  implemented a non-existent `App\Nexus\Modules\Menu\Contracts\MenuUrlResolverInterface`.
  A search across all Nexus projects on this machine (`Testovenexus`, `booksite`)
  confirmed: the interface is defined nowhere — this isn't a missing file, but a
  real bug. The class was switched to the already-existing package
  `Nodex\Nexus\Contracts\UrlResolverInterface` (the method signature was
  identical). Fixed both in the package and in the published copy in
  `app/Nexus/Modules/User/Services/`. See [menu-and-urls.md](menu-and-urls.md).

- **The `adminlte` template** was completely removed as a dead artifact: it had
  no Blade views on disk at all, only 99 MB of static assets
  (`resources/publish/adminlte`), of which only two
  vendor plugins were actually used (`dropzone`, `jquery-colorbox`) — these were moved to
  `resources/publish/packages/{dropzone,jquery-colorbox}` and the references to them
  in view files (`tailadmin/layouts/{adminpanel,blank}.blade.php`,
  the Auth/User login/google2fa views) were updated. `config('nexus.template')`
  now effectively supports only `tailadmin`.
- **Duplication of `config/nexus.php`** was partially resolved: the package config
  (`packages/nodex/nexus/src/config/nexus.php`) was synchronized with
  the one published in the application — `media_library` and
  `plugins.disabled` were moved over (they genuinely belong to the package, they simply used to live only in
  the application), the stale `dashboard.default` placeholder was fixed
  (`['helloWorld']` → `['usersCount', 'demoRecordsCount']`), and
  `admin_middleware` was supplemented with a commented-out example of
  `SetAdminLocale::class` (a real application class, so it isn't added as an
  active line — it remains only in the application's `config/nexus.php`).
  `graphql_prefix`/`graphql_middleware` are deliberately left only in
  the application — GraphQL is a paid add-on outside this repository (see
  the comment in the file itself and `../README.md`). Active behavior hasn't
  changed (the application's published config still takes precedence for
  shared keys, as before) — this is a cleanup of the package defaults, verified via
  `php artisan config:show nexus`.

Before release, these discrepancies should either be fixed in code, or deliberately
left "as is" with the corresponding notes removed from the documentation.
