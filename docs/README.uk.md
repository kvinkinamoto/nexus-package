# Документація пакета `nodex/nexus`

Nexus — це Laravel-пакет для швидкої побудови адмін-панелі: типи контенту
(«модулі») описуються PHP 8 атрибутами прямо на Eloquent-моделі, а пакет сам
збирає з них форму, таблицю списку, валідацію та (за потреби) API-ресурс.
Розширювати чужі модулі без власної таблиці БД можна плагінами; на дашборд
виносяться віджети; будь-який модуль/плагін може оголосити власні
налаштування без міграції.

Ця сторінка — вхідна точка в документацію. Встановлення пакета описане
окремо в [`../Instalation.md`](../Instalation.md); ліцензійні умови — в
[`../README.md`](../README.md).

Якщо ви користуєтесь лише безкоштовними модулями, підтримайте розвиток
проєкту донатом: [send.monobank.ua/jar/2V1YcJMoCr](https://send.monobank.ua/jar/2V1YcJMoCr).

## Розділи

| Документ | Про що |
| --- | --- |
| [modules.md](modules.md) | Система модулів — `#[Module]`, `#[Field]`, `#[Column]`, `#[Section]`, `#[Relation]`, `#[RepeaterField]`, `#[Permission]`, `#[Requests]`, валідація, скафолдинг `nexus:make:module`, кеш маніфесту модулів. |
| [field-types.md](field-types.md) | Довідник усіх `#[Field(type: ...)]` значень. **Автогенерується** командою `nexus:docs:field-types --markdown=docs/field-types.md` — не редагувати вручну, редагувати `FieldTypesDocsCommand::$builtIn`. |
| [plugins.md](plugins.md) | Плагіни й хуки — `#[TargetModule]`, `#[Filter]`/`#[Action]` (`nexus_filter()`/`nexus_action()`), `#[AttachField]`/`#[AttachColumn]`/`#[AttachFilter]`, `#[AttachRelation]`/`#[AttachScope]`, повний каталог подій `Events/**`, boot-timing пастка автовиявлення плагінів. |
| [widgets.md](widgets.md) | Дашборд-віджети — `#[Widget]`, контракти `WidgetInterface`/`ProvidesMetric`/`RendersHtml`/`ApiSerializable`, `nexus:make:widget`, кешування виводу, `@position()`. |
| [settings.md](settings.md) | Реєстр налаштувань — `#[Setting]`, зберігання в `nexus_module_settings`, сторінка «All Settings», читання/запис через `SettingsBuilder`. |
| [table-features.md](table-features.md) | Admin-таблиця — `#[TableAction]`/`#[TableGroupAction]` (бічна панель bulk-дій), `#[TableFilter]`/`#[TableLens]`, `#[TableImport]`/експорт, фолбек глобального пошуку, per-user видимість колонок. |
| [menu-and-urls.md](menu-and-urls.md) | Резолвери URL і меню — `#[Module(menuResolver:)]`, `UrlResolverInterface`, `RelatedEntityFieldService`/`RelatedEntityField`, подія `SidebarMenuBuilding`; розбір модулів `Menu`/`Redirect`/`Sitemap` як прикладу застосування. |
| [artisan-commands.md](artisan-commands.md) | Повний довідник усіх `nexus:*` artisan-команд пакета з точними сигнатурами. |
| [architecture.md](architecture.md) | Внутрішня «сантехніка» пакета — послідовність `NexusServiceProvider::register()`/`boot()`, конфіг, кеш маніфесту модулів, Blade-директиви, система шаблонів (tailadmin/adminlte), реєстрація типів полів. |

## Відомі проблеми, виявлені під час документування

Усе, що було знайдено при першому проході документації, вже виправлено.

### Виправлено після першого проходу документації

- **`UserMenuResolver`** (`#[Module(menuResolver:)]` стартового модуля
  `User`, `src/AppStubs/User.php.stub` / опублікований `app/Models/User.php`)
  реалізовував неіснуючий `App\Nexus\Modules\Menu\Contracts\MenuUrlResolverInterface`.
  Пошук по всіх Nexus-проєктах на цій машині (`Testovenexus`, `booksite`)
  підтвердив: інтерфейс ніде не визначений — це не пропущений файл, а
  реальний баг. Клас переведено на вже існуючий пакетний
  `Nodex\Nexus\Contracts\UrlResolverInterface` (сигнатура методу була
  ідентична). Виправлено і в пакеті, і в опублікованій копії в
  `app/Nexus/Modules/User/Services/`. Див. [menu-and-urls.md](menu-and-urls.md).

- **Шаблон `adminlte`** повністю видалений як мертвий артефакт: не мав
  жодного Blade-вʼю на диску, лише 99 МБ статичних ассетів
  (`resources/publish/adminlte`), з яких реально використовувались лише два
  вендорні плагіни (`dropzone`, `jquery-colorbox`) — вони перенесені в
  `resources/publish/packages/{dropzone,jquery-colorbox}` і посилання на них
  у view-файлах (`tailadmin/layouts/{adminpanel,blank}.blade.php`,
  Auth/User login/google2fa views) оновлені. `config('nexus.template')`
  тепер фактично підтримує лише `tailadmin`.
- **Дублювання `config/nexus.php`** частково усунуто: пакетний конфіг
  (`packages/nodex/nexus/src/config/nexus.php`) синхронізовано з
  опублікованим в застосунку — перенесено `media_library` та
  `plugins.disabled` (справді належать пакету, просто раніше жили лише в
  застосунку), виправлено застарілий плейсхолдер `dashboard.default`
  (`['helloWorld']` → `['usersCount', 'demoRecordsCount']`), а
  `admin_middleware` доповнено закоментованим прикладом
  `SetAdminLocale::class` (реальний клас застосунку, тому не додається як
  активний рядок — лишається лише в `config/nexus.php` застосунку).
  `graphql_prefix`/`graphql_middleware` свідомо залишені тільки в
  застосунку — GraphQL є платним аддоном поза цим репозиторієм (див.
  коментар у самому файлі та `../README.md`). Активна поведінка не
  змінилась (опублікований конфіг застосунку, як і раніше, головний для
  спільних ключів) — це чистка дефолтів пакета, перевірено через
  `php artisan config:show nexus`.

Перед релізом варто або виправити ці розбіжності в коді, або свідомо
залишити їх «як є» й прибрати відповідні примітки з документації.
