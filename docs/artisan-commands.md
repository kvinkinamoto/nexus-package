# Довідник Artisan-команд Nexus

Повний перелік `artisan`-команд, які реєструє пакет `nodex/nexus`
(`src/commands/`). Для загального опису встановлення й оновлення пакета
дивіться `Instalation.md` — тут кожна команда описана окремо, з точним
сигнатурою та поясненням того, що саме вона робить під капотом.

## Встановлення та оновлення

### `nexus:install`

```
php artisan nexus:install
```

Первинне розгортання пакета в застосунку. Створює директорії
`app/Nexus/Modules` та `app/Nexus/Plugins`, якщо їх ще немає, після чого
послідовно викликає `nexus:resource:publish`, стандартну Laravel-команду
`notifications:table` (шапка адмінки безумовно звертається до
`unreadNotifications()`, тож таблиця сповіщень потрібна навіть без жодного
власного модуля Nexus; `notifications:table` сама не публікує міграцію
повторно, якщо вона вже є), `migrate`, `nexus:permission:init` та
`nexus:module:install` (без аргументу — інсталює всі вже опубліковані
модулі). Публікація стартових модулів (`nexus:default_module:publish`) в цей
ланцюжок навмисно не входить — вона закоментована в коді команди, отже
`Auth`/`User`/`Permission`/`Role` потрібно опублікувати окремо до або після
`nexus:install`. Запускають цю команду один раз, одразу після
`composer require nodex/nexus`.

### `nexus:update`

```
php artisan nexus:update
```

Оновлення вже встановленого пакета до нової версії: виконує
`composer update nodex/nexus` через `exec()`, а тоді викликає
`nexus:resource:publish`, щоб перепублікувати конфіг/ресурси/JS/переклади
пакета поверх застосунку. Не чіпає модулі в `app/Nexus/Modules` — вони вже
скопійовані у застосунок і оновлюються так само, як звичайний код проєкту.

### `nexus:resource:publish`

```
php artisan nexus:resource:publish
```

Обгортка над чотирма викликами `vendor:publish` (теги `nexus-config`,
`nexus-resources-publish`, `nexus-js`, `nexus-lang`) — публікує конфіг,
ресурси, фронтенд-скрипти та мовні файли пакета в застосунок. Викликається
автоматично із `nexus:install` та `nexus:update`, але її можна запустити й
вручну, якщо потрібно лише "перетягнути" свіжі ресурси пакета без повного
циклу встановлення/оновлення.

### `nexus:module:install`

```
php artisan nexus:module:install {name?}
```

Реєструє (інсталює) модуль через `ModuleManager`. З аргументом `{name}`
інсталює конкретний модуль (ім'я автоматично приводиться до `Str::ucfirst`);
без аргументу проходить по всіх модулях, які повертає
`ModuleManager::getModules()`, і інсталює їх по черзі. Запускають після
`nexus:make:module` (для нового модуля) або після
`nexus:default_module:publish` (для стартових модулів), щоб модуль став
видимим у системі — на відміну від публікації файлів, саме ця команда
"вмикає" модуль.

### `nexus:default_module:publish`

```
php artisan nexus:default_module:publish 
                        {--module= : Module name or comma-separated list}
                        {--force : Overwrite existing modules}
```

Копіює стартові модулі пакета (`Auth`, `User`, `Permission`, `Role` —
з `src/Modules`) у `app/Nexus/Modules`, переписуючи неймспейс
`Nodex\Nexus\Modules\{Name}` на `App\Nexus\Modules\{Name}` у скопійованих
файлах. `--module=Auth,User` обмежує список модулів, які публікуються; без
опції публікуються всі. Якщо цільова директорія модуля вже існує, публікація
пропускається з попередженням — `--force` примусово видаляє й перезаписує
її (обережно: якщо міграція модуля вже виконана, повторний `--force`
перештампує файл і Laravel спробує застосувати "нову" міграцію повторно —
докладно про це в `Instalation.md`). Команда також переставляє часові мітки
міграцій модуля на поточний момент публікації, щоб вони гарантовано
відсортувались після вже наявних у `database/migrations` (включно з
щойно опублікованими міграціями `spatie/laravel-permission`), і додає ім'я
модуля в назву файлу міграції, щоб уникнути колізій між однаково названими
міграціями різних модулів. Окремо, якщо серед модулів, що публікуються, є
`User`, копіює `src/AppStubs/User.php.stub` у `app/Models/User.php` (та ж
логіка "не перезаписувати без `--force`") — Laravel завжди очікує
auth-модель саме за цим шляхом, тому вона не може жити всередині модуля.

## Генерація коду / скаффолдинг

### `nexus:make:module`

```
php artisan nexus:make:module {name}
```

Створює каркас нового адмін-модуля в `app/Nexus/Modules/{Name}` за
реальними конвенціями пакета: модель (`Models/{Name}.php`), реквести
(`Requests/AdminStoreRequest.php`, `Requests/AdminUpdateRequest.php`),
міграцію (`database/migrations/..._create_{pluralSnakeName}_table.php`),
файли перекладів (`resources/lang/en|uk/translate.php`) і сторінку
документації модуля (`resources/views/docs.blade.php`) — усе зі шаблонів
у `src/commands/stubs`. Падає з помилкою, якщо модуль з такою назвою вже
існує (перезапису немає, опції `--force` тут немає). Після генерації
виводить підказку про наступні кроки: доповнити модель полями/зв'язками
(дивись `nexus:docs:field-types`), виконати `migrate` і
`nexus:module:install {name}`. Це стартова точка для будь-якого нового
типу контенту в адмінці.

### `nexus:make:field`

```
php artisan nexus:make:field {name} {--module= : The module this field type belongs to}
```

Створює клас-рендерер кастомного типу поля (`FieldTypeRenderer`) разом з
Blade-партиалом у `{Module}/FieldTypes/`. Опція `--module` обов'язкова —
без неї команда завершується помилкою; якщо вказаного модуля не існує в
`app/Nexus/Modules`, виводить перелік доступних модулів і теж завершується
помилкою. Якщо клас з такою назвою вже є — помилка без перезапису.
Використовувати новий тип можна одразу через
`#[Field(type: '{lowerName}', ...)]` на властивості моделі — команда
попереджає, що виявлення автоматичне, але якщо в проєкті активний кеш
модулів (`bootstrap/cache/nexus-modules.php` існує), потрібно ще виконати
`nexus:module:cache`, щоб новий тип підхопився.

### `nexus:make:filter`

```
php artisan nexus:make:filter {module}
```

Створює обробник фільтрів модуля — клас `ModuleFilterHandler`, що
перевизначає `FilterHandler::filter()`, у `{Module}/Filters/`. Аргумент
`{module}` обов'язковий; якщо модуля не існує — виводить список доступних
модулів і завершується помилкою, так само як і якщо обробник для цього
модуля вже створено. Розпізнається автоматично за назвою (без ручної
реєстрації), але кожен доданий у ньому фільтр треба окремо оголосити в
`TableConfigDto` модуля (`FilterConfigDto`) — інакше він не з'явиться в
UI списку адмінки.

### `nexus:make:plugin`

```
php artisan nexus:make:plugin {name} {--module= : The name of the module to target}
```

Створює каркас плагіна (`{Name}Plugin.php` у `app/Nexus/Plugins/{Name}`) —
клас з атрибутами `#[TargetModule]`/`#[Filter]`/`#[Action]`, що розширює
поведінку існуючого модуля без власної таблиці в БД. `--module` задає
цільовий модуль (за замовчуванням `User`); ім'я нормалізується через
`Str::ucfirst`. Якщо файл плагіна вже існує — помилка без перезапису.
Плагін виявляється автоматично при наступному запиті; команда нагадує
прибрати непотрібні `#[Filter]`/`#[Action]`-методи й заповнити
`handle()`/`register()`/`boot()`.

### `nexus:make:widget`

```
php artisan nexus:make:widget {name}
```

Створює каркас дашборд-віджета: клас `{Name}.php` з атрибутом `#[Widget]`,
що імплементує `WidgetInterface`, і Blade-шаблон, розміщені разом в одній
папці `app/Nexus/Widgets/{Name}` (а не в `resources/views/widgets/`) —
саме тому, що `RendersHtml::render()` резолвить шаблон через `View::file()`
і `__DIR__`. Помилка без перезапису, якщо клас уже існує. Після генерації
підказує: віджет виявляється автоматично, а щоб він показувався за
замовчуванням на дашборді — додати його ключ у
`config('nexus.dashboard.default')`, або підключити вручну через пікер
"Customize" в самій адмінці.

## Кеш модулів

### `nexus:module:cache`

```
php artisan nexus:module:cache
```

Компілює файловий маніфест усіх модулів (views/routes/переклади/іконки/
команди/listeners) у `bootstrap/cache/nexus-modules.php`, щоб кожен запит
не сканував файлову систему наживо. Всередині оновлює `ModuleRegistry`
(`refresh()`), будує маніфест через `ModuleManifestCache::build()` і
записує його на диск. Виводить шлях до файлу кешу та кількість
скомпільованих модулів. Використовується як продакшн-оптимізація (аналог
`config:cache`/`route:cache`) — запускати після деплою або будь-якої зміни
складу модулів/плагінів.

### `nexus:module:clear`

```
php artisan nexus:module:clear
```

Видаляє скомпільований маніфест модулів, повертаючи застосунок до живого
сканування файлової системи на кожному запиті. Парна команда до
`nexus:module:cache` — виконувати перед розробкою локально (щоб нові
модулі/поля/плагіни підхоплювались одразу) або перед повторним
кешуванням після структурних змін.

## Права доступу та користувачі

### `nexus:permission:init`

```
php artisan nexus:permission:init
```

Синхронізує базові permissions адмін-панелі з кодом: проходить по всіх
кейсах `AdminPanelPermissionEnum`, формує людяну назву (`display_name.en`)
із значення еніма і робить `Permission::updateOrCreate()` для кожного —
тобто безпечно перезапускати повторно, нові permissions додасться, наявні
оновлять лише `display_name`. Викликається автоматично з `nexus:install` і
з `nexus:create:superadmin`, але її варто запускати вручну й після того, як
`AdminPanelPermissionEnum` поповнили новими значеннями вручну — щоб вони
з'явились у таблиці permissions.

### `nexus:create:superadmin`

```
php artisan nexus:create:superadmin {name?} {email?} {password?} {--name=} {--email=} {--password=}
```

Створює користувача з роллю `super-admin`, якій призначено геть усі
permissions гарда `web`. Ім'я/email/пароль можна передати позиційними
аргументами, іменованими опціями або (якщо не передані) команда запитає їх
інтерактивно (`ask()`/`secret()` для пароля). Спочатку викликає
`nexus:permission:init`, щоб permissions точно існували. Якщо користувач з
таким email вже є — команда лише повідомляє про це й нічого не створює
(не падає помилкою). Роль `super-admin` створюється через
`firstOrCreate()`, якщо її ще немає, і синхронізується з усіма наявними
permissions (`syncPermissions()`) при кожному запуску — тобто повторний
виклик оновить набір прав ролі, навіть якщо користувача вже створено раніше.
Це перша команда, яку запускають одразу після `nexus:install`, щоб мати
з чим увійти в адмінку.

### `nexus:api-token:issue`

```
php artisan nexus:api-token:issue {email}
```

Видає Sanctum personal access token для існуючого користувача за email
(модель береться з `config('auth.providers.users.model')`, тож команда не
прив'язана жорстко до `App\Models\User`). Якщо користувача з таким email
немає — помилка. Токен виводиться в консоль одноразово (`createToken('api')
->plainTextToken`) — це тимчасовий інструмент для REST/GraphQL-доступу,
поки в адмінці немає власного екрана самообслуговування для API-токенів.

## Документація

### `nexus:docs:field-types`

```
php artisan nexus:docs:field-types {--markdown= : Write the reference as a Markdown file to this path instead of printing a table}
```

Генерує повний довідник усіх значень `#[Field(type: ...)]`, які реально
вміє відрендерити Livewire-форма адмінки: вбудовані типи (з описом кожного
— `string`, `text`, `relation`, `blockEditor` тощо), аліаси і типи,
зареєстровані модулями/плагінами через `FieldTypeRegistry`. Без
`--markdown` виводить таблицю в консоль; з `--markdown={path}` записує
той самий довідник у Markdown-файл (саме так згенеровано
`docs/field-types.md` у цьому пакеті). Команда самоперевіряється: звіряє
захардкоджений масив описів (`$builtIn`) із реальними партиалами на диску
(`resources/views/tailadmin/livewire/field_types`) і виводить попередження
про типи, які є на диску, але не задокументовані, або задокументовані, але
партиала для них уже нема — щоб довідник не розходився з кодом непомітно.
Запускати після додавання нового вбудованого типу поля, або просто щоб
подивитись, які типи взагалі доступні для `#[Field(...)]`.
