## <h2 style="color:#ba363f">Dashboard Widgets</h2>

Widжет — це клас, що описує одну картку контенту чи метрики: картка з
числом на адмін-дашборді (`Total Users`), список останніх записів модуля,
або блок, вставлений на фронт через `@position()`. Один widget = одна папка
(`{Name}/{Name}.php` + за потреби co-located Blade-вʼю) — та сама конвенція
самодостатньої папки, що й у типів полів і плагінів. Реєстрація повністю
декларативна: клас позначається атрибутом `#[Widget(...)]`, реалізує
`WidgetInterface`, і система підхоплює його автоматично при наступному
запиті — жодного ручного `register()`.

Клас `Nodex\Nexus\Attributes\Widget` — авторитетне джерело метаданих
(`name`, `label`, `surfaces`, ...). Контракти в `Nodex\Nexus\Contracts\Widgets\*`
— авторитетне джерело того, що widget реально **вміє робити**.
`WidgetRegistry` звіряє одне з іншим і у разі розбіжності (наприклад,
`surfaces` включає `Front`, а `RendersHtml` не реалізований) не падає, а
лише пише попередження в лог і на цій поверхні widget просто не
відмальовується.

---

### Де живе widget

- Загальний, не привʼязаний до модуля: `app/Nexus/Widgets/{Name}/{Name}.php`
  під неймспейсом `App\Nexus\Widgets\{Name}`.
- Власний widget модуля: `app/Nexus/Modules/{Module}/Widgets/{Name}/{Name}.php`
  під неймспейсом `App\Nexus\Modules\{Module}\Widgets\{Name}` — виявляється
  окремо, у межах маніфесту саме цього модуля (приклад: `DemoRecordsCount`
  у модулі `Demo`).
- Вбудовані widget-и самого пакета лежать у `src/Widgets` під неймспейсом
  `Nodex\Nexus\Widgets`.

Скан відбувається по кожній підпапці `Widgets/`: папка `{FolderName}`
резолвиться у клас `{namespace}\Widgets\{FolderName}\{FolderName}` — назва
папки й назва класу повинні збігатися.

⚠️ **Маніфест-кеш.** Так само, як і модулі, widget-и виявляються через
скомпільований `bootstrap/cache/nexus-modules.php`. Якщо щойно доданий
widget не зʼявляється — виконайте `php artisan nexus:module:clear`.

---

### Атрибут `#[Widget(...)]`

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

| Параметр | Тип | За замовчуванням | Призначення |
| --- | --- | --- | --- |
| `name` | `string` | — (обовʼязковий) | Стабільний slug, що зберігається у розміщеннях widget-а (dashboard layout / widget instance) замість FQCN. **Ніколи не перейменовуйте** після того, як widget вже десь розміщено — інакше існуючі розміщення "осиротіють". |
| `label` | `string` | — (обовʼязковий) | Людська назва в UI (пікер, картка). |
| `surfaces` | `WidgetSurface[]` | `[WidgetSurface::Admin]` | На яких поверхнях widget може зʼявитись: `Admin`, `Front`, `Api`. |
| `icon` | `?string` | `null` | Іконка для картки/пікера. |
| `group` | `?string` | `null` | Ключ групування у widget-пікері. |
| `module` | `?string` | `null` | Модуль-власник — лише косметика для пікера, **не** функціональна залежність (на відміну від `requires`). |
| `cacheTtl` | `?int` | `null` | Секунди кешування результату `getData()`/`render()`. `null`/`0` — без кешування, перерахунок при кожному виклику. |
| `requires` | `string[]` | `[]` | Модулі, які мають бути увімкнені, щоб widget взагалі зареєструвався. |
| `apiPublic` | `bool` | `false` | Чи може `Api`-поверхня віддавати widget анонімному запиту. |
| `defaultSize` | `?string` | `null` | Підказка розміру сітки дашборду, напр. `'1x1'`, `'2x1'`. |
| `permission` | `?string` | `null` | Дозвіл (permission), потрібний для перегляду/розміщення widget-а в адмін-дашборді (`WidgetPermissionChecker`). |
| `lazy` | `bool` | `false` | Замість інлайн-обчислення на сторінці дашборду рендериться плейсхолдер, а реальний HTML довантажується через AJAX. |

`WidgetSurface` (`Nodex\Nexus\Enums\WidgetSurface`) — enum з трьома
значеннями: `Front`, `Admin`, `Api`.

---

### Контракти

`WidgetInterface` — єдиний обовʼязковий контракт:

```php
interface WidgetInterface
{
    public function getData(array $config, WidgetContext $context): array;
    public static function configFields(): array;
}
```

- `getData()` — єдина точка отримання даних; саме її викликає й `Api`-поверхня
  (json-кодує результат "як є", якщо немає `ApiSerializable`).
- `configFields()` — поля конфігурації, що показуються адміну при
  розміщенні/налаштуванні widget-а (`FieldConfigDto[]`, той самий формат,
  що й у полів модуля — `Dto/ModuleDtos/FieldConfigDto`). У всіх прикладах
  у проєкті наразі повертається `[]`.

Опційні можливості (можна реалізувати одну, кілька або жодну — залежно
від `surfaces`):

| Контракт | Коли потрібен | Методи |
| --- | --- | --- |
| `ProvidesMetric` | Одне число-метрика (картка без власного вʼю) — падає назад на вбудований `metric_card.blade.php`. | `toMetric(array $config, WidgetContext $context): MetricDto` |
| `RendersHtml` | Власна Blade-розмітка (для `Admin`/`Front`). | `availableViews(): array`, `viewFor(?string $name): ?string`, `render(array $config, WidgetContext $context): string` |
| `ApiSerializable` | Коли форма відповіді для `Api` має відрізнятись від `getData()` (приховати внутрішнє поле, змінити форму). За відсутності `Api`-поверхня бере `getData()` як є. | `toApiPayload(array $config, WidgetContext $context): array` |

**Правило узгодження `surfaces` ↔ контрактів** (перевіряє
`WidgetRegistry::hasCapabilityFor()`):

- `Admin` або `Front` у `surfaces` вимагає `RendersHtml` **або**
  `ProvidesMetric`. Реєстратор цього **не примушує** — лише логує
  попередження `Nexus: widget [...] declares surface [...] but implements
  neither RendersHtml nor ProvidesMetric.`, а на практиці widget мовчки
  нічого не рендерить на цій поверхні.
- `Api` не вимагає нічого понад `WidgetInterface::getData()`.

`RendersHtml::render()` очікує, що імʼя вʼю з `availableViews()`
резолвиться відносно **власної папки класу** (`__DIR__ . '/' . $view`) і
передається у `View::file()` — а не як dot-нотація `resources/views`. Це
навмисно: весь widget (клас + шаблон) лишається самодостатнім в одній
папці, а не розсипається по `resources/views/widgets/`.

---

### `WidgetContext` і `MetricDto`

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

Все, що `getData()`/`render()` можуть захотіти знати про те, де й як їх
викликають, зібрано тут в одному місці — нова можливість додається як
нове поле в `WidgetContext`, а не як новий параметр у сигнатурі кожного
методу widget-а. `$context->params['view']` — приклад використання:
`RecentDemoRecords::render()` бере звідти імʼя вʼю-варіанта.

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

`MetricDto` успадковує `\stdClass` — та сама конвенція, що й
`FieldConfigDto` в іншому місці пакета. Повернений з `toMetric()`, він
рендериться вбудованим `metric_card.blade.php` (`$metric->label`,
`$metric->value`, `$metric->unit`, `$metric->deltaPercent`,
`$metric->direction`, `$metric->icon`) — заповнювати можна лише потрібні
поля, решта підуть з дефолтами.

---

### Скаффолдинг: `php artisan nexus:make:widget`

```
php artisan nexus:make:widget {Name}
```

Створює:

- `app/Nexus/Widgets/{Name}/{Name}.php` — клас з `#[Widget(name: '{lowerName}', ...)]`,
  який за замовчуванням реалізує `WidgetInterface, RendersHtml` (не
  `ProvidesMetric`).
- `app/Nexus/Widgets/{Name}/{lowerName}.blade.php` — co-located заглушка вʼю.

Команда нічого не реєструє вручну — widget підхоплюється автоматично на
наступному запиті. Якщо потрібен widget, привʼязаний саме до модуля,
перенесіть згенеровану папку (і поправте неймспейс) у
`app/Nexus/Modules/{Module}/Widgets/{Name}/` вручну — окремого прапорця
для цього в команди немає.

Після генерації:

- Щоб widget показувався на кожному свіжому дашборді за замовчуванням —
  додайте `'{lowerName}'` у `config('nexus.dashboard.default')`.
- Інакше користувач додає його сам через пікер "Customize" на дашборді.

---

### Робочий приклад (ProvidesMetric + ApiSerializable)

Реальний widget з пакета, `app/Nexus/Widgets/UsersCount/UsersCount.php`:

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

    // Форма для API навмисно вужча за getData() — саме той кейс,
    // що описує докблок ApiSerializable.
    public function toApiPayload(array $config, WidgetContext $context): array
    {
        return ['totalUsers' => User::count()];
    }
}
```

Тут немає жодного власного Blade-файлу — на `Admin`-поверхні картку малює
вбудований `metric_card.blade.php` через `toMetric()`. `apiPublic`
лишений `false` (дефолт), бо кількість користувачів не має бути доступна
анонімному запиту.

### Робочий приклад (RendersHtml, дві поверхні)

`app/Nexus/Widgets/RecentDemoRecords/RecentDemoRecords.php` — перший у
проєкті widget із власною Blade-розміткою, одночасно на `Admin` і
`Front`:

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

Ту саму розмітку показує і адмін-дашборд, і `@position('some-slot')` на
фронті — конкретне розміщення на позиції налаштовує адмін через
`WidgetInstance`/`WidgetAssignment` (`App\Nexus\Modules\WidgetPlacement`),
а не сам клас widget-а.

### Модульний widget

`app/Nexus/Modules/Demo/Widgets/DemoRecordsCount/DemoRecordsCount.php` —
приклад widget-а, привʼязаного до власного модуля (лежить у
`{Module}/Widgets/`, а не в глобальному `app/Nexus/Widgets/`):

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

### Дефолтний дашборд

`config('nexus.dashboard.default')` — впорядкований список
`#[Widget(name:)]`-слагів, що показується на порожньому дашборді (0 рядків
у `nexus_dashboard_layouts`). У цьому проєкті наразі:

```php
'dashboard' => [
    'default' => ['usersCount', 'demoRecordsCount'],
],
```

Пріоритет вибору лейауту (`DashboardLayoutResolver::resolveFor()`):
власний рядок користувача в `nexus_dashboard_layouts` → єдиний рядок з
`is_default = true` → `config('nexus.dashboard.default')` → `[]`. Колонка
`role` у таблиці зарезервована під майбутній рівень "лейаут за роллю", але
поки що не використовується — фолбек одразу йде на `is_default`/`config`.

---

### Кешування виводу (`cacheTtl`)

`WidgetOutputCache::remember()` — єдина точка, через яку проходить кожен
споживач widget-виводу (`WidgetApiController`, `AdminDashboardRenderer`,
`WidgetInstance::render()`). Послідовність важлива:

1. Обчислюється `$output` (виклик `getData()`/`render()`/`toApiPayload()`).
2. Кидається подія `WidgetOutputResolving` ($kind — `'data'` для масиву або
   `'html'` для рядка).
3. Застосовується plugin-фільтр `widget.{kind}.{key}` (`nexus_filter()`).
4. **Тільки після цього**, якщо `cacheTtl` задано — результат кладеться в
   кеш під ключем `nexus:widget:{name}:{variantKey}`.

Тобто фільтр/listener бачить кожен виклик і сам потрапляє в кеш — якщо
плагін змінює вивід widget-а і зміна має лишатись актуальною, не
"воюйте" з кешем у фільтрі, а знижуйте/скидайте `cacheTtl` в
`#[Widget(...)]`. Без `cacheTtl` (`null`/`0`) кеш взагалі не чіпається —
`getData()`/`render()` виконується при кожному виклику.

### Події

- `WidgetOutputResolving` (`Nodex\Nexus\Events\WidgetOutputResolving`) —
  ланцюговий з фільтром `widget.{kind}.{key}`, `$output` передається по
  референсу.
- `DashboardLayoutResolving` (`Nodex\Nexus\Events\DashboardLayoutResolving`) —
  ланцюговий з фільтром `nexus.dashboard.layout`, `$layout` по референсу;
  дозволяє модулю перевизначити резолв дашборду (наприклад, за роллю) без
  окремого плагін-класу.

### Права доступу (`permission`)

`WidgetPermissionChecker::check()` — якщо `#[Widget(permission:)]` не
задано, доступ дозволено всім. Якщо задано — перевіряється
`$user->hasPermissionTo(...)`, з обходом для власника permission `ALL`
(`AdminPanelPermissionEnum::ALL`). Якщо названий permission взагалі не
засіяний (наприклад, `nexus:permission:init` ще не запускали) —
`hasPermissionTo()` кидає `PermissionDoesNotExist`, і перевірка деградує
до "відмовлено", а не до 500-ї помилки.

### Lazy-рендеринг (`lazy: true`)

Якщо `lazy: true`, дашборд одразу рендерить плейсхолдер
(`lazy_placeholder.blade.php`, спінер із `data-nexus-widget-lazy="{name}"`),
а реальний HTML довантажується AJAX-запитом до
`NexusController::widgetCard()` і підміняється в DOM після
`DOMContentLoaded`. Використовуйте для widget-ів, чий `getData()` занадто
повільний, щоб тримати відповідь дашборду.

### `@position()` на фронті

```blade
@position('sidebar-top')
@position('sidebar-top', 'landing')
```

Директива рендерить усі активні `WidgetAssignment`-рядки для вказаної
позиції (через `FrontWidgetRenderer`), впорядковані по `sort_order`. Другий
аргумент (`$templateType`) опціональний — без нього тип шаблону
резолвиться через `TemplateTypeResolver`. Розміщення widget-а на позиції —
окрема адмін-дія (`WidgetInstance`/`WidgetAssignment`), не частина самого
класу widget-а.

---

### Типові пастки

- **`surfaces` не збігається з реалізованими контрактами.** `Admin`/`Front`
  без `RendersHtml`/`ProvidesMetric` — не помилка реєстрації, а тихе
  попередження в лог + порожній рендер на цій поверхні. Перевіряйте лог,
  якщо widget "не показується".
- **`requires` не задоволено — тиша.** Якщо перелічений модуль не
  увімкнений, widget просто не реєструється: без помилки, без запису в
  лог (`WidgetRegistry::hasUnmetRequirements()` — рання `return`, без
  `Log::warning`). "Зниклий" widget у пікері часто означає саме це, а не
  баг.
- **Маніфест-кеш.** Новий файл widget-а (глобальний чи модульний) виявляється
  через той самий скомпільований `bootstrap/cache/nexus-modules.php`, що
  й модулі. Не зʼявляється — `php artisan nexus:module:clear`.
- **`name` — persisted identity.** Це слаг, не імʼя класу, саме тому, що
  перейменування PHP-класу не повинно "осиротити" наявні
  розміщення на дашборді/фронті. Змінювати `name` у widget-а, що вже
  десь розміщений, — тільки з планом міграції існуючих розміщень.
- **Кешування діє вже після фільтрів.** `WidgetOutputCache::remember()`
  кешує результат **після** події `WidgetOutputResolving` і фільтра
  `widget.{kind}.{key}` — щоб змінити щойно закешований вивід, керуйте
  `cacheTtl`, а не намагайтесь перехопити виклик усередині кешу.
- **Папка ≠ вʼю з `resources/views`.** `RendersHtml::render()` очікує
  імена файлів відносно `__DIR__` самого класу widget-а (`View::file()`),
  а не dot-нотацію `resources/views/...` — Blade-файл обовʼязково
  лежить поруч із класом в одній папці.
