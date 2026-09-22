# Admin Table: actions, filters, lenses, import/export, search, columns

This document describes the capabilities of the Nexus reactive admin table (`Nodex\Nexus\Livewire\ModuleTable` + `resources/views/tailadmin/livewire/module-table.blade.php`), which is rendered for any module with `#[Module(livewire: true)]`. Every statement below has been verified against the package code (`packages/nodex/nexus/src`), not invented — where something could not be found in the code, that is stated explicitly.

## 1. `#[TableAction]` and `#[TableGroupAction]` — row actions

### 1.1 `#[TableAction]` — an action on a single row (or the table's "main" action)

Class: `Nodex\Nexus\Attributes\TableAction` (`Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE` — attached to the module's model multiple times).

| Parameter | Type | Default | Purpose |
| --- | --- | --- | --- |
| `name` | `string` | — | Action name (`edit`, `delete`, `duplicate`, or any custom name) |
| `label` | `string` | — | Translation key under `nexus::translate.{label}` |
| `icon` | `string` | — | Icon key for `nexus_icon()` |
| `isConfirm` | `bool` | `false` | Whether to show a `wire:confirm` / JS confirmation before execution |
| `isMain` | `bool` | `false` | `true` registers the action as a table-level "main" action (e.g. "Create") instead of a row action |
| `isActive` | `bool` | `true` | `false` registers the action but hides/deactivates it (e.g. disable "Create" for read-only data) |

These attributes are read by `Nodex\Nexus\Services\AttributeSchemaReader::processTableAttrs()`: `isMain: true` is placed into `$config->table->mainActions`, otherwise into `$config->table->actions`.

Rendering in `module-table.blade.php`:
- `mainActions` are drawn as buttons with a brand background in the table header (next to Import/Export/Settings/Columns);
- row actions are output in the `$tableData['actions']` loop. A small whitelist, `SINGLE_RECORD_ACTIONS` (`delete`, `restore`, `deletePermanent`, `duplicate`), is executed reactively via `wire:click="runAction('{name}', '{id}')"` → `ModuleTable::runAction()`. `edit` for a module with `#[Module(slideOver: true)]` opens a side panel (`openSlideOver()`), otherwise it is a plain link. Any other (custom) action is rendered as `<a href="…route('nexus.module.action', …)…">` without a Livewire call — this is a full-page request to `NexusController::action()`; `isConfirm` in that case is confirmed via `sendFormConfirm()` (JS in `indexLivewire.blade.php`), not `wire:confirm`.

### 1.2 `#[TableGroupAction]` — a group (bulk) action on selected rows

Class: `Nodex\Nexus\Attributes\TableGroupAction` (also `TARGET_CLASS | IS_REPEATABLE`).

| Parameter | Type | Purpose |
| --- | --- | --- |
| `name` | `string` | Action name (`deleteGroup`, `publishGroup`, or any custom name) |
| `fieldName` | `string` | Passed as `action` in `ActionGroupConfigDto`. For the 5 built-in names (`deleteGroup`, `restoreGroup`, `publishGroup`, `unpublishGroup`, `duplicateGroup`) it is effectively ignored — `ModuleTable::BUILT_IN_GROUP_ACTIONS` and `BulkActionJob::BUILT_IN_GROUP_ACTIONS` execute them directly via the corresponding `*ActionGroupMethod` classes. For any other name, `fieldName` is the name of the hook/method looked up by `CallGroupActionMethod`/`CallModuleHookAction` (before/after) |

**UI: a docked left sidebar, not a toolbar/dropdown.** If a module has at least one active `#[TableGroupAction]`, `module-table.blade.php` renders `<div x-data x-show="$wire.selected.length > 0" x-transition:… class="fixed inset-y-0 left-0 z-50 … w-72 …">` — a fixed panel spanning the full viewport height, attached to the **left** edge of the screen, which slides out (`-translate-x-full` → `translate-x-0`) as soon as at least one row checkbox is checked (`x-show` reads `$wire.selected.length` directly, a reactive Alpine proxy over the Livewire property). Inside it there is a `{{ count($selected) }} selected` counter, a button to clear the selection (`$set('selected', [])`), and a list of **all** active `actionGroup`s at once (`wire:click="runGroupAction('{name}')"`, with `wire:confirm` when `confirm: true`) — this is not toolbar buttons or a dropdown menu, so adding new bulk actions requires no markup changes.

Execution: `ModuleTable::runGroupAction()`:
1. Fires `Nodex\Nexus\Events\BulkActionExecuting` (a Laravel event) and the `nexus.bulk_action.executing` filter — `$ids` is passed by reference, and the listener can partially "veto" the selection (remove some ids) or throw an exception to cancel the whole action.
2. If `count($selected) > 50` (`ModuleTable::ASYNC_BULK_ACTION_THRESHOLD`) — `Nodex\Nexus\Modules\BulkAction\Jobs\BulkActionJob` is dispatched (a queued job that processes the selection in chunks of 100, writes progress to `Cache`, and is polled by the browser through the same `nexus.module.export.progress` endpoint as export — the route name isn't export-specific, it just reads the value by `cacheKey`).
3. Otherwise the action is executed synchronously within the same request via `CallGroupActionMethod` (for custom `actionGroup`s) or directly via the corresponding `*ActionGroupMethod` (for the 5 built-in names).

## 2. `#[TableFilter]` and `#[TableLens]` — filtering and saved views

### 2.1 `#[TableFilter]`

Class: `Nodex\Nexus\Attributes\TableFilter` (`TARGET_CLASS | IS_REPEATABLE`).

| Parameter | Type | Default | Purpose |
| --- | --- | --- | --- |
| `name` | `string` | — | Filter key (`filter.{name}` in Livewire state) |
| `label` | `string` | — | Translation key |
| `type` | `string` | — | In `module-table.blade.php` only `'search'` (a text field with `wire:model.live.debounce.400ms`) and `'select'` with a non-empty `optionsModel` (`<select>`) are actually handled. Other values from `AdminAvailableFilterEnum` (`trashed`, `is_published`, `relation`, `depth`, `date`) have no dedicated UI block in this view — no matching `@elseif` for them was found in `module-table.blade.php` |
| `optionsModel` | `?string` | `null` | Only for `type: 'select'` — the Eloquent model class whose rows populate the `<option>`s; re-queried on every render |
| `optionsValue` | `string` | `'id'` | Column — the `<option>` value (and the value passed to `FilterHandler`) |
| `optionsLabel` | `string` | `'name'` | Column — the `<option>` label, also used for `orderBy` |

Processing: `AttributeSchemaReader::processTableAttrs()` places the filter into `$config->table->filters` via `TableConfigDto::filter()`. Applying the value to the query — `AddFilterActionMethod::handle()` (more details in section 4 — this is where the "generic search fallback" logic is hidden).

### 2.2 `#[TableLens]`

Class: `Nodex\Nexus\Attributes\TableLens` (`TARGET_CLASS | IS_REPEATABLE`). Per the attribute's own docblock, this is a "Nova-style Lens without a custom query" — it reuses the same condition format as `UniversalFilterBuilder`.

| Parameter | Type | Default | Purpose |
| --- | --- | --- | --- |
| `name` | `string` | — | Unique key, read from `?lens=` (or from the Livewire `lens` state) |
| `label` | `string` | — | Translation key under `{module}::translate`, shown on the tab |
| `conditions` | `array` | `[]` | Array of conditions in `UniversalFilterBuilder::apply()` format: `['column' => …, 'operator' => …, 'value' => …, 'logic' => 'AND'\|'OR']` |
| `icon` | `?string` | `null` | Tab icon (`nexus_icon()`) |
| `columns` | `?array` | `null` | Column names to show while this lens is active — overrides the normal default/user-saved set of visible columns |
| `sort` | `?string` | `null` | Default sort column while the lens is active (an explicit user `?sort=` still takes priority) |

Example from the attribute's docblock:

```php
#[TableLens(name: 'published', label: 'lens_published', conditions: [
    ['column' => 'is_published', 'operator' => '=', 'value' => 1],
])]
#[TableLens(name: 'out_of_stock', label: 'lens_out_of_stock', conditions: [
    ['column' => 'stock', 'operator' => '<=', 'value' => 0],
])]
```

Rendering: `module-table.blade.php` draws lenses as tabs above the table (`wire:click="selectLens('{name}')"`), each with a count badge (`$tableData['lensCounts'][$lensName]`) — this is a separate `COUNT()` query for each lens, independent of active filters/search. `TableBuilder::build()`: if an active lens is found, its `conditions` are merged with any arbitrary `dyn` filters (the Universal Filters panel), and the lens's `columns`/`sort` override the normal visible-column and default-sort logic.

## 3. `#[TableImport]` and the Export module — CSV import/export

### 3.1 Export — enabled by default, no attribute needed

The package has no `#[TableExport]` attribute — the `Attributes/Table*.php` folder contains only `TableAction`, `TableGroupAction`, `TableFilter`, `TableLens`, `TableImport`. Export is enabled automatically: `DefaultModuleConfigurationDto::__construct()` always registers `exports: ['export' => new ExportConfigDto('export', 'Export')]` — meaning every module gets an "Export" button right away unless `$table->exports` is explicitly cleared.

Flow:
1. `module-table.blade.php` — the `wire:click="exportTable"` button (visible when `$module->config->table->exports` is not empty).
2. `ModuleTable::exportTable()` dispatches `Nodex\Nexus\Modules\Export\Jobs\MasterExportJob` with the current `filter`/`sort`/`lens` state — meaning the table is exported "as currently filtered," not as an independent full snapshot.
3. `MasterExportJob` reuses `TableBuilder::build(..., sql: true)` — the same query used for the normal table render — and writes CSV to `php://temp` in chunks (`chunkSize`, default 5000), with progress in `Cache` (`{progress, processed, total, status}`).
4. For each row, `Nodex\Nexus\Events\ExportRowBuilding` fires (`$row` — an ordered array of CSV cells, by reference) along with the `nexus.export.row` filter — allowing a module/plugin to reformat or edit values without a separate export pipeline.
5. The finished file is placed on `Storage::disk('local')` at `exports/{module}_{Ymd_His}_{6-character cacheKey}.csv`.
6. The browser polls progress via `nexus.module.export.progress?cacheKey=…` (JS `pollNexusProgress()` in `indexLivewire.blade.php`) and, when `status === 'completed'`, navigates to `nexus.module.export.download`.

### 3.2 `#[TableImport]` — optional, because it writes data

Class: `Nodex\Nexus\Attributes\TableImport` (`TARGET_CLASS`, **not** repeatable). Per the docblock: unlike `TableAction`/`TableGroupAction`, import has no auto-registered default — the module must explicitly declare that it wants this capability, because it writes data.

| Parameter | Type | Default | Purpose |
| --- | --- | --- | --- |
| `name` | `string` | — | Import name |
| `label` | `?string` | `null` | Button label |
| `icon` | `string` | `''` | Icon |
| `confirm` | `bool` | `true` | Whether to confirm before running |
| `isActive` | `bool` | `true` | Enables/disables it |

Example from the attribute's docblock:

```php
#[TableImport(name: 'import', label: 'Import')]
```

Flow (`Nodex\Nexus\Services\Actions\Admin\ImportActionMethod`, **synchronous**, no queue — unlike export):
1. The Import button (a file `<input type="file" accept=".csv,text/csv">`) sends a `fetch()` request to `{admin_prefix}/{module}/import` (a delegated `change` handler in `indexLivewire.blade.php`, so it survives Livewire's DOM morphing).
2. The CSV header is matched against the module's columns by `label` **or** by `name` (`$columnsByLabel`/`$columnsByName`, built from `$moduleConfig->table->columns`).
3. For each row: if there is an `id` column, `find()`+`update()` is performed, otherwise `create()`. For each row, `Nodex\Nexus\Events\ImportRowBuilding` fires (`$data` by reference) along with the `nexus.import.row` filter — **before** intersecting with `$fillable`, so a plugin can transform values but cannot slip in a field the model doesn't allow mass assignment for anyway.
4. "Bad" rows (an exception during `create`/`update`) are skipped (`skipped++`) rather than aborting the whole file.
5. The response is JSON `{created, updated, skipped}`, which the JS displays in `#nexusImportStatus-{module}` and afterward triggers a `$refresh` on the Livewire component.

## 4. Generic search fallback

The package actually has **two independent** search mechanisms — worth not confusing them.

### 4.1 Search inside a module's table (`#[TableFilter(type: 'search')]`)

The value of the text search field is sent as `filter.search` and handled by `Nodex\Nexus\Services\Actions\Admin\AddFilterActionMethod::handle()`:

```php
$filterModuleHandler = ModuleManager::getClassFromModule($module->name.'\\Filters\\ModuleFilterHandler')
    ?? new FilterHandler;
```

If the module **does not have its own** `{Module}/Filters/ModuleFilterHandler.php`, the base `Nodex\Nexus\Filters\FilterHandler` is used. Its `filter()` method generically implements two "reserved" filter names for free, for any module:

- **`search`** — `LOWER(column) LIKE '%term%'` (via `orWhereRaw`) across **all physical columns of the model's table** (`SchemaColumnsCache::get($table)`), not only columns marked `searchable` — meaning the default table search "blindly" LIKEs every physical DB column, ignoring whether it even has `#[Column(searchable:)]`;
- **`trashed`** — if the model uses `SoftDeletes`, toggles `withTrashed()`/`onlyTrashed()`/`withoutTrashed()`.

The `php artisan nexus:make:filter {module}` command (`Nodex\Nexus\commands\MakeFilterCommand`) generates exactly this kind of `Filters/ModuleFilterHandler.php` (extends `FilterHandler`, overrides `filter()`) — it auto-resolves by name, no manual registration needed; the command also reminds you that any filter added inside it must be duplicated in `TableConfigDto->filters` (i.e. declared via `#[TableFilter]`/`$table->filter()`), otherwise no UI will appear for it in the admin panel.

### 4.2 Cross-module search — `GlobalSearchService`

This is a separate service (`Nodex\Nexus\Services\GlobalSearchService`) that searches **across all enabled modules at once**, unlike the "blind" LIKE in 4.1: it only uses columns explicitly marked `#[Column(searchable: true)]` (`ColumnConfigDto::$searchable`), so as to never expose internal/sensitive columns (password hashes, tokens, FKs). For every module that has at least one `searchable` column and an allowed `index` permission, an `orWhere(column, 'LIKE', "%term%")` is run over those columns, limited to `$limitPerModule` (default 5) rows; the result's label comes from the first `searchable` column (`array_key_first`), and the link goes to `edit` via `route('nexus.module.action', …)`. At the end, `Nodex\Nexus\Events\GlobalSearchCompleted` fires (`$results` by reference) along with the `nexus.search.results` filter — allowing a plugin to add its own result group (an external API, a non-module source) or to re-rank/trim what's already been collected.

## 5. Per-user column visibility (the column picker)

Storage: the `nexus_user_table_preferences` table (migration `2025_01_01_000000_create_nexus_tables.php`):

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

That is, one row per `(user_id, module)` pair; `visible_columns` is a JSON array of column names. (The `filters` column in the same table exists for saved "Universal Filters" — `NexusController::saveDynamicFilters()` — and has no direct relation to the column picker.)

**Reading** — `TableBuilder::build()`: if `$userId` is passed, a `nexus_user_table_preferences` row is read by `(user_id, module)`; if it exists and `visible_columns` is not empty, visible columns are filtered by exactly that list; otherwise the default is: all columns with `ColumnConfigDto::$tableDefault !== false`. If there is an active lens with its own `columns`, it overrides both cases (section 2.2).

**Writing** — two independent paths that write to the same row in the same way:
- The Livewire path (reactive, applicable for `#[Module(livewire: true)]`): the "Columns" dropdown in `module-table.blade.php` (a button with the `bx-columns` icon, visible only when there is more than 1 column) — a checkbox for each column with `wire:click="toggleColumnVisibility('{name}')"` → `ModuleTable::toggleColumnVisibility()`, which takes the currently visible set from the already-computed `$tableData['columns']`, adds/removes the column, and performs `DB::table('nexus_user_table_preferences')->updateOrInsert(...)`. An empty resulting set (an attempt to remove the last visible column) is ignored — the method returns without changing anything.
- The HTTP path: `NexusController::saveTableColumns()` — the same `updateOrInsert`, kept for non-Livewire calls (a legacy route such as `PUT/POST {module}/columns` or similar — the route itself was not verified in this document).

## 6. Example: combining attributes on a hypothetical module

Below is an example in the style of the real `packages/nodex/nexus/src/Modules/Role/Models/Role.php` (the `#[Module]` + `#[TableAction]`/`#[TableGroupAction]` + `#[Column]`/`#[Field]` structure is confirmed against that file), combining search, a select filter, two lenses, a bulk action, import, and a custom `ModuleFilterHandler`:

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

Along with such a module, it's worth generating a custom search handler (otherwise search will work via the "blind" LIKE over all physical columns, section 4.1):

```
php artisan nexus:make:filter ShopProduct
```

— this creates `App\Nexus\Modules\ShopProduct\Filters\ModuleFilterHandler`, where you can narrow `search` down to specific columns (e.g. only `name`/`sku`) instead of a LIKE over the entire table.

`publishGroup` here is a built-in name (`ModuleTable::BUILT_IN_GROUP_ACTIONS`), so `fieldName: 'is_published'` is formally unused by the engine (execution goes directly through `PublishActionGroupMethod`), but it's still worth documenting the same way the built-in default values are documented in `DefaultModuleConfigurationDto`.
