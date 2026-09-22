## <h2 style="color:#ba363f">Dashboard Widgets</h2>

A widget is a class that describes a single piece of content or a metric card: a card with
a number on the admin dashboard (`Total Users`), a list of a module's most recent records,
or a block inserted on the front end via `@position()`. One widget = one folder
(`{Name}/{Name}.php` plus, if needed, a co-located Blade view) — the same self-contained
folder convention used for field types and plugins. Registration is fully
declarative: the class is marked with the `#[Widget(...)]` attribute, implements
`WidgetInterface`, and the system picks it up automatically on the next
request — no manual `register()` call needed.

The `Nodex\Nexus\Attributes\Widget` class is the authoritative source of metadata
(`name`, `label`, `surfaces`, ...). The contracts in `Nodex\Nexus\Contracts\Widgets\*`
are the authoritative source of what a widget can actually **do**.
`WidgetRegistry` cross-checks the two, and in case of a mismatch (for example,
`surfaces` includes `Front` but `RendersHtml` is not implemented) it does not fail — it
just logs a warning, and on that surface the widget simply doesn't
render.

---

### Where a widget lives

- A general-purpose widget, not tied to a module: `app/Nexus/Widgets/{Name}/{Name}.php`
  under the `App\Nexus\Widgets\{Name}` namespace.
- A module's own widget: `app/Nexus/Modules/{Module}/Widgets/{Name}/{Name}.php`
  under the `App\Nexus\Modules\{Module}\Widgets\{Name}` namespace — discovered
  separately, within that module's own manifest (example: `DemoRecordsCount`
  in the `Demo` module).
- The package's built-in widgets live under `src/Widgets`, under the
  `Nodex\Nexus\Widgets` namespace.

Scanning happens per `Widgets/` subfolder: a folder `{FolderName}`
resolves to the class `{namespace}\Widgets\{FolderName}\{FolderName}` — the folder
name and the class name must match.

⚠️ **Manifest cache.** Just like modules, widgets are discovered via the
compiled `bootstrap/cache/nexus-modules.php`. If a newly added
widget doesn't show up, run `php artisan nexus:module:clear`.

---

### The `#[Widget(...)]` attribute

```php
#[Widget(
    name: 'usersCount',
    label: 'Total Users',
    surfaces: [WidgetSurface::Admin, WidgetSurface::Api],
    icon: 'bx bx-group',
    group: 'stats',
    defaultSize: '1x1',
)]
class UsersCount implements ApiSerializable, ProvidesMetric, WidgetInterface
{
    // ...
}
```

| Parameter | Type | Default | Purpose |
| --- | --- | --- | --- |
| `name` | `string` | — (required) | Stable slug stored in the widget's placements (dashboard layout / widget instance) instead of an FQCN. **Never rename it** once the widget has been placed somewhere — otherwise existing placements become "orphaned". |
| `label` | `string` | — (required) | Human-readable name in the UI (picker, card). |
| `surfaces` | `WidgetSurface[]` | `[WidgetSurface::Admin]` | Which surfaces the widget can appear on: `Admin`, `Front`, `Api`. |
| `icon` | `?string` | `null` | Icon for the card/picker. |
| `group` | `?string` | `null` | Grouping key in the widget picker. |
| `module` | `?string` | `null` | Owning module — cosmetic only for the picker, **not** a functional dependency (unlike `requires`). |
| `cacheTtl` | `?int` | `null` | Seconds to cache the result of `getData()`/`render()`. `null`/`0` — no caching, recomputed on every call. |
| `requires` | `string[]` | `[]` | Modules that must be enabled for the widget to register at all. |
| `apiPublic` | `bool` | `false` | Whether the `Api` surface can serve the widget to an anonymous request. |
| `defaultSize` | `?string` | `null` | Dashboard grid size hint, e.g. `'1x1'`, `'2x1'`. |
| `permission` | `?string` | `null` | The permission required to view/place the widget on the admin dashboard (`WidgetPermissionChecker`). |
| `lazy` | `bool` | `false` | Instead of computing inline on the dashboard page, a placeholder is rendered and the real HTML is loaded via AJAX. |

`WidgetSurface` (`Nodex\Nexus\Enums\WidgetSurface`) is an enum with three
values: `Front`, `Admin`, `Api`.

---

### Contracts

`WidgetInterface` is the only required contract:

```php
interface WidgetInterface
{
    public function getData(array $config, WidgetContext $context): array;
    public static function configFields(): array;
}
```

- `getData()` — the single point for fetching data; it's also what the `Api`
  surface calls (JSON-encodes the result "as is" if there's no `ApiSerializable`).
- `configFields()` — configuration fields shown to the admin when
  placing/configuring the widget (`FieldConfigDto[]`, the same format
  used for module fields — `Dto/ModuleDtos/FieldConfigDto`). In every example
  in this project it currently returns `[]`.

Optional capabilities (you can implement one, several, or none — depending
on `surfaces`):

| Contract | When needed | Methods |
| --- | --- | --- |
| `ProvidesMetric` | A single-number metric (a card with no view of its own) — falls back to the built-in `metric_card.blade.php`. | `toMetric(array $config, WidgetContext $context): MetricDto` |
| `RendersHtml` | Custom Blade markup (for `Admin`/`Front`). | `availableViews(): array`, `viewFor(?string $name): ?string`, `render(array $config, WidgetContext $context): string` |
| `ApiSerializable` | When the `Api` response shape needs to differ from `getData()` (hide an internal field, reshape it). Without it, the `Api` surface takes `getData()` as is. | `toApiPayload(array $config, WidgetContext $context): array` |

**The `surfaces` ↔ contracts consistency rule** (checked by
`WidgetRegistry::hasCapabilityFor()`):

- `Admin` or `Front` in `surfaces` requires **either** `RendersHtml`
  **or** `ProvidesMetric`. The registrar does **not** enforce this — it only
  logs the warning `Nexus: widget [...] declares surface [...] but implements
  neither RendersHtml nor ProvidesMetric.`, and in practice the widget silently
  renders nothing on that surface.
- `Api` requires nothing beyond `WidgetInterface::getData()`.

`RendersHtml::render()` expects the view name from `availableViews()` to
resolve relative to **the class's own folder** (`__DIR__ . '/' . $view`) and
be passed to `View::file()` — not as `resources/views` dot notation. This is
intentional: the whole widget (class + template) stays self-contained in one
folder instead of being scattered across `resources/views/widgets/`.

---

### `WidgetContext` and `MetricDto`

```php
final class WidgetContext
{
    public function __construct(
        public readonly WidgetSurface $surface,
        public readonly ?string $position = null,
        public readonly ?string $templateType = null,
        public readonly ?int $instanceId = null,
        public readonly ?Authenticatable $user = null,
        public readonly ?string $locale = null,
        public readonly array $params = [],
    ) {}
}
```

Everything `getData()`/`render()` might want to know about where and how they
are being called is gathered here in one place — a new capability is added as
a new field on `WidgetContext`, rather than as a new parameter in every
widget method's signature. `$context->params['view']` is an example of this:
`RecentDemoRecords::render()` reads the view-variant name from there.

```php
class MetricDto extends \stdClass
{
    public function __construct(
        public string $label,
        public int|float|string $value,
        public int|float|string|null $previous = null,
        public ?float $deltaPercent = null,
        /** 'up' | 'down' | 'flat' | null */
        public ?string $direction = null,
        /** Sparkline points, oldest first. */
        public array $series = [],
        public ?string $unit = null,
        public ?string $icon = null,
        public ?string $link = null,
    ) {}
}
```

`MetricDto` extends `\stdClass` — the same convention used by
`FieldConfigDto` elsewhere in the package. Returned from `toMetric()`, it is
rendered by the built-in `metric_card.blade.php` (`$metric->label`,
`$metric->value`, `$metric->unit`, `$metric->deltaPercent`,
`$metric->direction`, `$metric->icon`) — you only need to fill in the fields
you need, the rest fall back to defaults.

---

### Scaffolding: `php artisan nexus:make:widget`

```
php artisan nexus:make:widget {Name}
```

Creates:

- `app/Nexus/Widgets/{Name}/{Name}.php` — a class with `#[Widget(name: '{lowerName}', ...)]`,
  which by default implements `WidgetInterface, RendersHtml` (not
  `ProvidesMetric`).
- `app/Nexus/Widgets/{Name}/{lowerName}.blade.php` — a co-located view stub.

The command doesn't register anything manually — the widget is picked up automatically on
the next request. If you need a widget tied specifically to a module,
move the generated folder (and fix the namespace) to
`app/Nexus/Modules/{Module}/Widgets/{Name}/` manually — there's no dedicated flag
for this in the command.

After generation:

- For the widget to show up on every fresh dashboard by default,
  add `'{lowerName}'` to `config('nexus.dashboard.default')`.
- Otherwise the user adds it themselves via the "Customize" picker on the dashboard.

---

### Working example (ProvidesMetric + ApiSerializable)

A real widget from the package, `app/Nexus/Widgets/UsersCount/UsersCount.php`:

```php
namespace App\Nexus\Widgets\UsersCount;

use App\Models\User;
use Nodex\Nexus\Attributes\Widget;
use Nodex\Nexus\Contracts\Widgets\ApiSerializable;
use Nodex\Nexus\Contracts\Widgets\ProvidesMetric;
use Nodex\Nexus\Contracts\Widgets\WidgetInterface;
use Nodex\Nexus\Dto\Widgets\MetricDto;
use Nodex\Nexus\Dto\Widgets\WidgetContext;
use Nodex\Nexus\Enums\WidgetSurface;

#[Widget(
    name: 'usersCount',
    label: 'Total Users',
    surfaces: [WidgetSurface::Admin, WidgetSurface::Api],
    icon: 'bx bx-group',
    group: 'stats',
    defaultSize: '1x1',
)]
class UsersCount implements ApiSerializable, ProvidesMetric, WidgetInterface
{
    public function getData(array $config, WidgetContext $context): array
    {
        return ['count' => User::count()];
    }

    public static function configFields(): array
    {
        return [];
    }

    public function toMetric(array $config, WidgetContext $context): MetricDto
    {
        return new MetricDto(
            label: 'Total Users',
            value: User::count(),
            icon: 'bx bx-group',
        );
    }

    // The API shape is intentionally narrower than getData() — exactly the
    // case the ApiSerializable docblock describes.
    public function toApiPayload(array $config, WidgetContext $context): array
    {
        return ['totalUsers' => User::count()];
    }
}
```

There's no Blade file of its own here — on the `Admin` surface the card is drawn by
the built-in `metric_card.blade.php` via `toMetric()`. `apiPublic`
is left `false` (the default), because the user count shouldn't be available to
an anonymous request.

### Working example (RendersHtml, two surfaces)

`app/Nexus/Widgets/RecentDemoRecords/RecentDemoRecords.php` — the first
widget in the project with its own Blade markup, on both `Admin` and
`Front` at once:

```php
#[Widget(
    name: 'recentDemoRecords',
    label: 'Recent Demo Records',
    surfaces: [WidgetSurface::Admin, WidgetSurface::Front],
    icon: 'solar:list-bold',
    group: 'demo',
    module: 'demo',
)]
class RecentDemoRecords implements RendersHtml, WidgetInterface
{
    public function getData(array $config, WidgetContext $context): array
    {
        $limit = max(1, (int) ($config['limit'] ?? 5));

        return [
            'records' => Demo::query()->latest('id')->limit($limit)->get(['id', 'title', 'status']),
        ];
    }

    public static function configFields(): array
    {
        return [];
    }

    public function availableViews(): array
    {
        return ['recentDemoRecords.blade.php' => 'Default'];
    }

    public function viewFor(?string $name): ?string
    {
        $views = $this->availableViews();

        return $name && isset($views[$name]) ? $name : array_key_first($views);
    }

    public function render(array $config, WidgetContext $context): string
    {
        $view = $this->viewFor($context->params['view'] ?? null);
        $path = $view ? __DIR__.DIRECTORY_SEPARATOR.$view : null;

        if (! $path || ! is_file($path)) {
            return '';
        }

        return view()->file($path, $this->getData($config, $context))->render();
    }
}
```

The same markup is shown both on the admin dashboard and via `@position('some-slot')` on
the front end — the specific placement on a position is configured by the admin through
`WidgetInstance`/`WidgetAssignment` (`App\Nexus\Modules\WidgetPlacement`),
not by the widget class itself.

### A module-scoped widget

`app/Nexus/Modules/Demo/Widgets/DemoRecordsCount/DemoRecordsCount.php` —
an example of a widget tied to its own module (it lives under
`{Module}/Widgets/`, rather than in the global `app/Nexus/Widgets/`):

```php
namespace App\Nexus\Modules\Demo\Widgets\DemoRecordsCount;

#[Widget(
    name: 'demoRecordsCount',
    label: 'Published Demo Records',
    surfaces: [WidgetSurface::Admin],
    icon: 'bx bx-widget',
    group: 'stats',
    module: 'demo',
    defaultSize: '1x1',
)]
class DemoRecordsCount implements ProvidesMetric, WidgetInterface
{
    public function getData(array $config, WidgetContext $context): array
    {
        return [
            'published' => Demo::where('status', DemoStatus::PUBLISHED->value)->count(),
            'total' => Demo::count(),
        ];
    }

    public static function configFields(): array
    {
        return [];
    }

    public function toMetric(array $config, WidgetContext $context): MetricDto
    {
        $published = Demo::where('status', DemoStatus::PUBLISHED->value)->count();
        $total = Demo::count();

        return new MetricDto(
            label: 'Published Demo Records',
            value: $published,
            unit: " / {$total}",
            icon: 'bx bx-widget',
        );
    }
}
```

---

### Default dashboard

`config('nexus.dashboard.default')` is an ordered list of
`#[Widget(name:)]` slugs shown on an empty dashboard (0 rows
in `nexus_dashboard_layouts`). In this project it currently is:

```php
'dashboard' => [
    'default' => ['usersCount', 'demoRecordsCount'],
],
```

Layout selection priority (`DashboardLayoutResolver::resolveFor()`):
the user's own row in `nexus_dashboard_layouts` → the single row with
`is_default = true` → `config('nexus.dashboard.default')` → `[]`. The
`role` column in the table is reserved for a future "layout by role" level, but
it isn't used yet — the fallback goes straight to `is_default`/`config`.

---

### Output caching (`cacheTtl`)

`WidgetOutputCache::remember()` is the single point every
consumer of widget output goes through (`WidgetApiController`, `AdminDashboardRenderer`,
`WidgetInstance::render()`). The order matters:

1. `$output` is computed (calling `getData()`/`render()`/`toApiPayload()`).
2. The `WidgetOutputResolving` event fires (`$kind` — `'data'` for an array or
   `'html'` for a string).
3. The `widget.{kind}.{key}` plugin filter is applied (`nexus_filter()`).
4. **Only after that**, if `cacheTtl` is set, the result is stored in the
   cache under the key `nexus:widget:{name}:{variantKey}`.

In other words, the filter/listener sees every call and ends up in the cache itself — if
a plugin changes a widget's output and the change needs to stay current, don't
"fight" the cache from inside the filter; instead, lower/reset `cacheTtl` in
`#[Widget(...)]`. Without `cacheTtl` (`null`/`0`) the cache isn't touched at all —
`getData()`/`render()` runs on every call.

### Events

- `WidgetOutputResolving` (`Nodex\Nexus\Events\WidgetOutputResolving`) —
  chainable with the `widget.{kind}.{key}` filter, `$output` is passed by
  reference.
- `DashboardLayoutResolving` (`Nodex\Nexus\Events\DashboardLayoutResolving`) —
  chainable with the `nexus.dashboard.layout` filter, `$layout` by reference;
  allows a module to override dashboard resolution (e.g. by role) without
  a separate plugin class.

### Permissions (`permission`)

`WidgetPermissionChecker::check()` — if `#[Widget(permission:)]` isn't
set, access is allowed for everyone. If it is set, `$user->hasPermissionTo(...)`
is checked, with a bypass for holders of the `ALL` permission
(`AdminPanelPermissionEnum::ALL`). If the named permission hasn't been
seeded at all (e.g. `nexus:permission:init` hasn't been run yet), then
`hasPermissionTo()` throws `PermissionDoesNotExist`, and the check degrades
to "denied" rather than a 500 error.

### Lazy rendering (`lazy: true`)

If `lazy: true`, the dashboard immediately renders a placeholder
(`lazy_placeholder.blade.php`, a spinner with `data-nexus-widget-lazy="{name}"`),
and the real HTML is loaded via an AJAX request to
`NexusController::widgetCard()` and swapped into the DOM after
`DOMContentLoaded`. Use this for widgets whose `getData()` is too
slow to keep in the dashboard's response.

### `@position()` on the front end

```blade
@position('sidebar-top')
@position('sidebar-top', 'landing')
```

The directive renders all active `WidgetAssignment` rows for the given
position (via `FrontWidgetRenderer`), ordered by `sort_order`. The second
argument (`$templateType`) is optional — without it, the template type
is resolved via `TemplateTypeResolver`. Placing a widget on a position is a
separate admin action (`WidgetInstance`/`WidgetAssignment`), not part of the
widget class itself.

---

### Common pitfalls

- **`surfaces` doesn't match the implemented contracts.** `Admin`/`Front`
  without `RendersHtml`/`ProvidesMetric` isn't a registration error — it's a silent
  log warning plus an empty render on that surface. Check the log
  if a widget "isn't showing up".
- **`requires` unmet — silence.** If a listed module is not
  enabled, the widget simply doesn't register: no error, no log entry
  (`WidgetRegistry::hasUnmetRequirements()` — an early `return`, no
  `Log::warning`). A "missing" widget in the picker often means exactly this, not a
  bug.
- **Manifest cache.** A new widget file (global or module-scoped) is discovered
  through the same compiled `bootstrap/cache/nexus-modules.php` as
  modules. If it doesn't show up — `php artisan nexus:module:clear`.
- **`name` is a persisted identity.** It's a slug, not the class name, precisely so that
  renaming the PHP class doesn't "orphan" existing
  placements on the dashboard/front end. Changing the `name` of a widget that is already
  placed somewhere should only be done with a migration plan for the existing placements.
- **Caching happens after filters.** `WidgetOutputCache::remember()`
  caches the result **after** the `WidgetOutputResolving` event and the
  `widget.{kind}.{key}` filter — to change output that's just been cached, manage
  `cacheTtl`, rather than trying to intercept the call from inside the cache.
- **A folder ≠ a view from `resources/views`.** `RendersHtml::render()` expects
  file names relative to the widget class's own `__DIR__` (`View::file()`),
  not `resources/views/...` dot notation — the Blade file must
  live next to the class, in the same folder.
