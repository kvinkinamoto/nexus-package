<h2 style="color:#ba363f">Modules</h2>

A module in Nexus is a self-contained content type for the admin panel: an Eloquent
model whose create/edit form, list table, validation, and (if needed)
API resource are described by PHP 8 attributes right on the model class. No
separate `ModuleConfiguration` builder needs to be written — the attributes are read by
`AttributeSchemaReader`, which assembles a DTO from them that drives the entire admin panel
(form, table, ajax relations, validation). The `ModuleConfiguration` class as a
separate entity is still supported (`#[Module]` can be placed not on the
model itself, but on a config class next to it — see the `model` parameter below), but in the
package's real modules it is not used: attributes always live on the model.

A module is automatically registered in the system as soon as `ModuleRegistry` finds
a class with `#[Module(...)]` in the enabled `Modules/**` directory — there's no need to
write a separate `ModuleServiceProvider` for each module.

Ready-made and additional modules can be found on the project website: [https://www.nexus-cms.shop/](https://www.nexus-cms.shop/).

## 1. Minimal working example

Below is a simplified but fully working module model, built following
the conventions actually used in the package (see `Modules/Form/Models/Form.php`):

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

This is exactly what `php artisan nexus:make:module Demo` generates (see section 9).
The `HasAttributeSchemaProperties` trait is required on every
attribute-driven module model — it's what lets you access `$name` as
a regular model property, even though it's declared `protected`.

## 2. `#[Module(...)]`

The attribute is placed on the model class (`Attribute::TARGET_CLASS`) and declares
the very existence of the module.

| Parameter | Type | Default | Description |
| --- | --- | --- | --- |
| `name` | `string` | — (required) | Unique camelCase module name, e.g. `'article'`, `'shopProduct'`. |
| `label` | `string` | `''` | Human-readable name shown in the admin menu. |
| `icon` | `string` | `'solar:box-bold'` | Icon (Solar Icons or FontAwesome). |
| `group` | `string` | `'Site'` | Parent menu group, e.g. `'Shop'`, `'Users'`, `'Content'`. |
| `showInMenu` | `bool` | `true` | Whether to show the module in the side menu. |
| `permissions` | `bool` | `true` | Automatically generate CRUD permissions for the module. |
| `isTree` | `bool` | `false` | Whether the module represents a tree structure. |
| `menuResolver` | `?string` | `null` | Custom menu item resolver class (implements `UrlResolverInterface`). A real example is `App\Nexus\Modules\User\Services\UserMenuResolver`. |
| `model` | `?string` | `null` | Explicit Eloquent model class, if `#[Module]` is placed not on the model itself (e.g., a separate `ModuleConfiguration` class wrapping a vendor model). Defaults to the annotated class itself. |
| `requires` | `array` | `[]` | Names of other modules required for this one to work (e.g., `Wishlist` requires `ShopProduct`). Purely informational — it does **not** block installation or enabling; a missing dependency is highlighted by a banner in the admin panel (`ModuleDependencyChecker`). |
| `wizard` | `bool` | `false` | Render the form as a step-by-step wizard instead of a single page. Requires at least one `#[Section(tab:)]` — existing tabs become steps with Next/Back navigation. |
| `slideOver` | `bool` | `false` | Open the edit form in an offcanvas panel from the list instead of navigating to a separate page. |
| `livewire` | `bool` | `false` | A legacy flag from the phased migration to Livewire — currently **inactive**, no controller logic reacts to it. Kept for backward compatibility with older `#[Module(...)]` declarations. |

```php
#[Module(name: 'article', label: 'Статті', icon: 'solar:document-bold', group: 'Content')]
class Article extends Model { ... }
```

## 3. `#[Field(...)]`

Placed on a public/protected property or a relation method
(`TARGET_PROPERTY | TARGET_METHOD`) and declares a form field.

| Parameter | Type | Default | Description |
| --- | --- | --- | --- |
| `type` | `string` | — (required) | Field type. For the full list of built-in types and what each one renders, see [`docs/field-types.md`](./field-types.md). |
| `section` | `string` | `'main'` | Key of the form section the field belongs to (see `#[Section]`). |
| `label` | `?string` | `null` | Human-readable name. If `null`, it's generated from the property name. |
| `required` | `bool` | `true` | Whether the field is required. |
| `translated` | `bool` | `false` | Whether the field is multilingual (requires `Spatie\Translatable\HasTranslations` + `public $translatable` on the model). |
| `editor` | `bool` | `false` | Enable a WYSIWYG editor (applies to `type: 'text'`). |
| `default` | `mixed` | `null` | Default value. |
| `enum` | `?string` | `null` | Backed enum class for `select`/`radio`/`enum` options. |
| `action` | `?string` | `null` | Action name for interactive fields (e.g., `'boolToggle'`). |
| `actionField` | `?string` | `null` | Name of the DB field for the action, if it differs from the property name. |
| `disabled` | `bool\|string` | `false` | Disable the field. A string value of `'create'` or `'edit'` limits the disabling to that context only. |
| `view` | `?string` | `null` | For `type: 'view'` — a Blade view `namespace::path`, which receives `$model`, `$field`, `$module`. |
| `relationConfig` | `array` | `[]` | For `type: 'relation'` — additional override parameters, e.g. `['path_field' => 'path']`. |
| `order` | `int` | `0` | Output order within the section (lower — higher up). Defaults to the declaration order in the class. |
| `rules` | `string\|array` | `[]` | Validation rules for both store and update at once. A string separated by `\|` or an array. For translatable fields it's automatically applied to each locale (`field.*`). |
| `storeRules` | `string\|array` | `[]` | Rules for store only (override `rules`). |
| `updateRules` | `string\|array` | `[]` | Rules for update only (override `rules`). |
| `showWhen` | `array` | `[]` | Conditions for showing the field: an array `['field' => '...', 'op' => 'eq\|neq\|in\|notIn\|truthy\|falsy\|gt\|lt\|contains', 'value' => ...]`. Empty — always shown. |
| `showWhenLogic` | `string` | `'and'` | How multiple `showWhen` conditions are combined: `'and'` or `'or'`. |
| `clearWhenHidden` | `bool` | `false` | Clear the value on the client when the field is hidden. |
| `apiExpose` | `bool` | `false` | Whether to include the field in the auto-generated API resource (`NexusResource::toArray()`). By default the field is admin-only. |
| `showInInfolist` | `bool` | `true` | Whether to show the field on the read-only view screen (Infolist). Disable for technical/sensitive fields (e.g., `password`). |
| `slugSource` | `?string` | `null` | For `type: 'slug'` — the name of another field in the same form whose value the "generate" button slugifies. |

```php
#[Column(label: 'Title', sortable: true, searchable: true)]
#[Field(type: 'string', section: 'main', label: 'Title', required: true, rules: ['max:255'])]
protected $title;
```

### How validation rules are derived from `#[Field]`

If a module does **not** declare `#[Requests(...)]`, gathering the validation rules
is handled by `Nodex\Nexus\Services\Validation\NexusRuleCollector`. For every
non-relation field, the order is as follows:

1. Default rules for the type (`FieldTypeRegistry::getDefaultRules($field->type)`).
2. `rules`, then `storeRules`/`updateRules` (depending on the action) — added on top.
3. If the rules contain neither `required` nor `nullable`, one is substituted —
   `required` (when `required: true`) or `nullable`.
4. For a translatable field (`translated: true`), the key becomes `{name}.*`.
5. For a `showWhen` field hidden given the current input values, the rule
   is forcibly replaced with `['exclude']`.

For relation fields (`type: 'relation'` or fields with `#[RepeaterField]`),
the collector itself knows neither the related table nor the pivot-data structure — it
only substitutes a minimal baseline (`required`/`nullable`, plus `array`
for multiple relations), just enough for the `relation.{name}` key to
survive Laravel's `validated()` at all.

**Important:** as soon as a module declares `#[Requests(actions: [...])]`, field-level
`rules`/`storeRules`/`updateRules` for the `store`/`update` actions are **no longer
taken into account** — the `rules()` method of the corresponding Request class must on its own
cover every field, including relation fields in the form of `relation.*` keys
(`'relation.tags' => 'nullable|array'`, `'relation.tags.*' => 'integer'`).
An unmentioned relation key silently skips validation, and the relation simply won't
be saved — without any error. To avoid manually duplicating
`NexusRuleCollector` logic in such a Request class, use the trait
`Nodex\Nexus\Concerns\ComposesNexusRules` — it calls `NexusRuleCollector`
inside `rules()` and lets you override only what the collector can't handle
(`extraRules()`: uniqueness checks, `Rule::exists()`, cross-field logic).

## 4. `#[Column(...)]`

Placed on the same property/method as `#[Field]` (optional — you can
have `#[Field]` without `#[Column]` if the field shouldn't appear in the list table).
Configures a column in the module's list table.

| Parameter | Type | Default | Description |
| --- | --- | --- | --- |
| `label` | `?string` | `null` | Column header. |
| `sortable` | `bool` | `false` | Whether the column is sortable. |
| `action` | `?string` | `null` | A special interactive action in the column. Built-in values: `'boolToggle'`, `'ordering'`. |
| `fieldName` | `?string` | `null` | Name of the DB field, if it differs from the property name — used by the `boolToggle` action to know which column to update. |
| `customField` | `?string` | `null` | Name of a custom Blade partial from `templates/custom_index_fields/` for rendering this cell. |
| `actionConfirm` | `bool` | `false` | Show a confirmation dialog before performing the action. |
| `tableDefault` | `bool` | `true` | Whether the column is visible in the table by default (affects the column-visibility picker for the user). |
| `order` | `int` | `0` | Order in the table (lower — further left). Defaults to the declaration order in the class. |
| `searchable` | `bool` | `false` | Explicit opt-in to the global cross-module search (`GlobalSearchService`). Unlike the per-module search filter, which LIKE-matches all physical columns, global search requires an explicit flag so internal/irrelevant columns don't "leak" in. |

```php
#[Column(label: 'Активний', action: 'boolToggle', fieldName: 'is_active')]
#[Field(type: 'boolean', section: 'settings')]
public bool $is_active;
```

## 5. `#[Section]` / `#[SectionColumn]`

`#[Section]` is placed on the model class (repeatable, `IS_REPEATABLE`) and
declares a named form section and its position in the layout grid.

| Parameter | Type | Default | Description |
| --- | --- | --- | --- |
| `name` | `string` | — (required) | Unique section key, referenced by `#[Field(section: '...')]`. |
| `column` | `string` | `'right'` | Layout column the section belongs to. Must match a `SectionColumn` name (`'left'`, `'right'`, or a custom one). |
| `type` | `string` | `'base'` | Section rendering type (maps to a Blade partial in `templates/sections/`). Built-in values: `'base'`, `'columns_2'`, `'information'`. |
| `icon` | `string` | `'solar:box-bold'` | Icon for the section card header. |
| `tab` | `?string` | `null` | Key of the tab this section's column is attached to. |

```php
#[Section(name: 'main',     column: 'left',  type: 'columns_2', icon: 'solar:document-bold')]
#[Section(name: 'settings', column: 'right', type: 'base',      icon: 'solar:settings-bold')]
```

`#[SectionColumn]` (also on the class, repeatable) overrides the CSS class
(width) of a layout column. By default `'left'`/`'right'` exist with
classes `col-lg-8`/`col-lg-4`.

| Parameter | Type | Description |
| --- | --- | --- |
| `name` | `string` | Name of the column being overridden. |
| `class` | `string` | CSS class (e.g., `'col-lg-8'`). |
| `tab` | `?string` | Optional binding to a specific tab. |

```php
#[SectionColumn(name: 'left', class: 'col-lg-8')]
#[SectionColumn(name: 'right', class: 'col-lg-4')]
```

## 6. `#[Relation]`

Placed alongside `#[Field(type: 'relation'|'images'|'repeater'|...)]` on a
method (or a property — for a relation coming from a trait, like
`Spatie\Permission`'s `HasRoles::roles()`, which therefore has no own method on the
model; in that case `type` must be specified explicitly). Configures how an
Eloquent relation is presented in the admin panel.

| Parameter | Type | Default | Description |
| --- | --- | --- | --- |
| `type` | `?string` | `null` | Relation type: `'belongsTo'`, `'hasMany'`, `'belongsToMany'`, `'hasOne'`. If not specified, Nexus tries to determine it automatically from the method's return type. |
| `show` | `string` | `'name'` | Field of the related model shown in selects and labels. |
| `showFallback` | `?string` | `null` | Fallback field (both in the label and search) used when the `show` value is empty for a specific record. |
| `required` | `bool` | `false` | Whether selecting a value is required. |
| `ajax` | `bool` | `false` | `false` — the option list is preloaded (up to 50), behaving like a regular select (suitable for small tables: roles, categories). `true` — nothing is preloaded, results appear only as you type (unless `ajaxMode: 'load'` is set). |
| `ajaxMode` | `string` | `'search'` | Relevant only with `ajax: true`. `'search'` — results only from the search query (for large tables). `'load'` — everything is preloaded and search is also supported (for medium tables). |
| `ajaxResource` | `?string` | `null` | Custom API Resource class for transforming the ajax response. |
| `relatedModule` | `?string` | `null` | The nexus module name of the related model — required for `#[Field(type: 'relationManager')]`, lets you link rows to that module's edit/delete actions and build a "view all" link filtered to the parent record. |

```php
// BelongsToMany, small table — rendered as a select
#[Field(type: 'relation', section: 'roles_and_permissions')]
#[Relation(type: 'belongsToMany', show: 'name')]
public function roles(): BelongsToMany { ... }

// BelongsTo with ajax search, large table
#[Field(type: 'relation', section: 'settings')]
#[Relation(type: 'belongsTo', show: 'name', ajax: true, ajaxMode: 'search')]
public function category(): BelongsTo { ... }
```

## 7. `#[RepeaterField]`

Declares a single column of a `repeater`-type field (`TARGET_METHOD`, repeatable) —
stacked on the same `hasMany` method, one column per
`#[RepeaterField]`, in declaration order. The relation method itself still
requires `#[Field(type: 'repeater', ...)]` + `#[Relation(type: 'hasMany', ...)]`
like any other `HasMany` — `#[RepeaterField]` only adds column
metadata on top.

| Parameter | Type | Default | Description |
| --- | --- | --- | --- |
| `name` | `string` | — (required) | Column key — on submit becomes `relation[{relationName}][{index}][{name}]`. |
| `type` | `string` | — (required) | Cell type, resolved the same way as any `#[Field]` type (module override → registry → built-in). |
| `label` | `?string` | `null` | Column header. Auto-generated from `name` if `null`. |
| `required` | `bool` | `false` | Whether the column is required. |
| `rules` | `string\|array` | `[]` | Validation rules for this column — applied by `NexusRuleCollector` as `relation.{name}.*.{column}`. |
| `width` | `?string` | `null` | CSS width of the `<th>`/`<td>`, e.g., `'120px'` or `'20%'`. |
| `showWhen` | `array` | `[]` | Currently collected, but not yet functionally applied (planned for per-row conditional visibility). |
| `showWhenLogic` | `string` | `'and'` | Logic for combining `showWhen`. |

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

## 8. Auxiliary attributes

### `#[Permission]`

Registers a custom permission for a module action, so that it appears in the
permissions management panel and can be assigned to roles/users from there. It does **not**
assign the permission to anyone automatically — that's done manually by the admin. Can be placed
on the model class (for global custom permissions of the module) or on a custom
controller method (for a specific action's permission).

| Parameter | Type | Default | Description |
| --- | --- | --- | --- |
| `action` | `string` | — (required) | Action key — becomes the suffix of the permission name: `{moduleName}_{action}`. |
| `label` | `?string` | `null` | Human-readable label in the permissions panel. Auto-generated from `action` if `null`. |
| `guard` | `string` | `'web'` | Guard under which the permission is registered. |

```php
#[Permission(action: 'run', label: 'Run backup now')]
```

### `#[Requests]`

Binds custom `FormRequest` classes to module actions by action key (`'store'`,
`'update'`, `'restore'`, `'boolToggle'`, or any custom group action name).
Resolved by `GetModuleRequestAction::getRequestByMethodName()` for
any action, not just `store`/`update`. See section 3 for how this
affects the source of validation rules.

```php
#[Requests(actions: ['store' => AdminStoreRequest::class, 'update' => AdminUpdateRequest::class])]
```

### `#[Composer]`

Registers a Blade view-composer class for the module's own views (a class on the
model class, repeatable).

```php
#[Composer(class: DemoIndexComposer::class)]
```

### `#[MethodResource]`

Binds a separate API Resource class (with eager-load hints) to one
custom controller method, instead of that method falling back to
the general `NexusResource` fallback.

| Parameter | Type | Description |
| --- | --- | --- |
| `method` | `string` | Controller method name. |
| `resourceClass` | `string` | API Resource class. |
| `with` | `array` | List of relations to eager-load. |

```php
#[MethodResource(method: 'export', resourceClass: DemoExportResource::class, with: ['items'])]
```

## 9. Scaffolding a new module

```
php artisan nexus:make:module {name}
```

The command takes a name (automatically converted to `UcfirstCamel`) and, if
a module with that name doesn't already exist in `app/Nexus/Modules/{Name}`, generates:

- `Models/{Name}.php` — a model with `#[Module]`, basic `#[TableAction]`
  (`edit`/`delete`), `#[TableGroupAction(deleteGroup)]`, `#[Requests]`,
  one `main` section, and one `name` field — a minimal working module,
  ready right after migration.
- `Requests/AdminStoreRequest.php`, `Requests/AdminUpdateRequest.php` —
  empty `NexusFormRequest` descendants with the rule `'name' => 'required|string'`.
- `database/migrations/{timestamp}_create_{plural_snake}_table.php`.
- `resources/lang/en/translate.php`, `resources/lang/uk/translate.php`.
- `resources/views/docs.blade.php`.

The command then suggests the next steps itself:

```
1. Add the fields/relations you need to Models/{Name}.php
2. php artisan migrate
3. php artisan nexus:module:install {Name}
```

`nexus:module:install {name}` (without a name — all modules) creates a
`Module` row in the DB, runs migrations, and registers the module's own `Widgets/` folder
in the live registry.

## 10. Module manifest cache

If the file `bootstrap/cache/nexus-modules.php` exists, module discovery
(as well as field-types, widgets, listeners, translations) is served from the
compiled manifest instead of a live filesystem scan. This means that a
**newly added or modified module will not appear** until the cache is
rebuilt:

```
php artisan nexus:module:cache   # compile/rebuild the manifest immediately
php artisan nexus:module:clear   # delete the manifest, revert to live scanning
```

After any structural change under `app/Nexus/Modules/**` (a new module,
a new field type, a new listener), check whether
`bootstrap/cache/nexus-modules.php` exists, and run `nexus:module:clear` (or
`nexus:module:cache` right away, to avoid waiting for the live scan on the next
request).

## Field types reference

A full list of the built-in `type` values for `#[Field]`/`#[RepeaterField]` and
a brief description of each is in [`field-types.md`](./field-types.md).
