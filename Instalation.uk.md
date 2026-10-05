## <h2 style="color:#ba363f">Installation</h2>

Вимоги: PHP ^8.3, Laravel 13, Vite + Tailwind CSS v4 (`@tailwindcss/vite`), Node.js/npm.

### Порядок установки

1. Встановіть пакет:
   ```
   composer require nodex/nexus
   ```
2. Опублікуйте базові таблиці `spatie/laravel-permission` (вони мають існувати **до** публікації модулів `Permission`/`Role`):
   ```
   php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
   ```
3. Опублікуйте стартові модулі:
   ```
   php artisan nexus:default_module:publish --module=Auth,User,Permission,Role
   ```
4. Встановіть бібліотеку (ресурси, тема адмінки, міграції, реєстрація модулів):
   ```
   php artisan nexus:install
   ```
5. Зберіть фронтенд адмінки:
   ```
   npm install && npm run build
   ```
6. Створіть супер-адміністратора:
   ```
   php artisan nexus:create:superadmin
   ```
7. Відкрийте `/admin` (префікс змінюється змінною `ADMIN_PREFIX`).

> Якщо стартові модулі опублікували **пізніше**, ніж виконали `nexus:install`, вони не зареєстровані в адмінці
> (модуль з'являється в адмінці лише коли має запис у таблиці `nexus_modules`). Виконайте
> `php artisan nexus:module:install` (без імені — усі опубліковані модулі) або
> `php artisan nexus:module:install User` для одного модуля.

⚠️ Без публікації базових модулів спроба доступу до `/admin` закінчиться звичайною помилкою 404 (замість 500).
Це нормальна поведінка. Підказка, як опублікувати модулі, завжди пишеться в лог (`storage/logs`), а на сторінці
показується лише при `APP_DEBUG=true`.

## Стартові модулі (Auth, User, Permission, Role)

Пакет несе разом із собою 4 базові модулі — без них нема з чим зайти в адмінку
(`Auth` — форма логіну, `Permission`/`Role` — обгортки над
`spatie/laravel-permission`, `User` — Eloquent-модель, на яку авторизується
Laravel). Живуть у `src/Modules/{Auth,User,Permission,Role}` пакета під
неймспейсом `Nodex\Nexus\Modules\{Name}` і публікуються командою з кроку 3
(без `--module` вона публікує всі стартові модулі одразу). Команда сама переписує
неймспейс `Nodex\Nexus\Modules\{Name}` → `App\Nexus\Modules\{Name}` у
скопійованих файлах. Якщо модуль з такою назвою вже існує в
`app/Nexus/Modules`, публікація пропускається (не перезаписує) — додайте
`--force`, щоб перезаписати свідомо.

Сторінки `Auth` (логін, реєстрація, відновлення пароля) самодостатні: використовують `resources/css/app.css`,
`resources/js/app.js` і Livewire (Alpine постачається разом з ним), без окремого JS магазину.
Соціальний вхід (`SocialAuth`) та публічний кабінет покупця (`Account`) — окремі додаткові модулі, у стартовий
набір не входять. Модулі `Cart`/`Wishlist` необов'язкові: `Auth`/`User` працюють і без них.

**Модель `App\Models\User`** — окремий випадок: вона не лежить всередині
жодного модуля (Laravel завжди чекає auth-модель саме за шляхом
`app/Models/User.php`), тому та сама команда публікації додатково копіює
`src/AppStubs/User.php.stub` → `app/Models/User.php`, коли серед модулів, що
публікуються, є `User` (наявний файл не перезаписується без `--force`, за одним винятком: стандартна
`app/Models/User.php` від Laravel — та, що не використовує `UserModelTrait` — копіюється у
`app/Models/User.php.bak` і замінюється stub-ом; без цієї заміни `assignRole()` і вхід в адмінку падають з
`Call to undefined method App\Models\User::assignRole()`). Якщо ваша модель `User` уже змінена, злийте її
вручну з `src/AppStubs/User.php.stub` (або скопіюйте stub поверх:
`copy vendor\nodex\nexus\src\AppStubs\User.php.stub app\Models\User.php`). Це навмисно **мінімальний** stub — тільки те, що реально
використовують ці 4 модулі (`UserModelTrait` → `HasRoles`, нотифікації
скидання пароля/підтвердження email, зв'язок `addresses()` на `UserAddress` з
цього ж модуля). Якщо в проєкті пізніше з'являються модулі `Cart`/`Wishlist`/
`ActivityLog` — відповідні зв'язки/трейти дописуються в `app/Models/User.php`
вручну, це вже не файл пакета, а звичайний файл застосунку.

⚠️ **Порядок для `Permission`/`Role`**: їхні власні міграції
(`add_display_field`) роблять `Schema::table('permissions'/'roles', ...)` —
тобто вимагають, щоб базові таблиці від `spatie/laravel-permission` вже
існували. Laravel виконує pending-міграції за сортуванням імені файлу, а не
за смисловою залежністю, тож `nexus:default_module:publish` **сам
перештамповує** всі міграції модуля поточним часом публікації (той самий
трюк, яким Laravel публікує власні package-міграції) — це гарантує, що вони
відсортуються *після* всього, що вже лежить у `database/migrations`,
включно зі щойно опублікованою spatie-міграцією. Тому крок 2 (spatie) обов'язково
виконується **до** кроку 3 — таймстемп модуля ставиться в момент публікації, і якщо
опублікувати модуль РАНІШЕ spatie-міграції, проблема повернеться.

Якщо модуль уже був опублікований і його міграція вже виконалась —
`--force` republish **перештампує файл заново**, і Laravel спробує
виконати "нову" міграцію повторно (`Duplicate column`). Не робіть `--force`
на модулі, чия міграція вже в статусі `Ran`.

## Що робить `nexus:install`

`php artisan nexus:install` створює `app/Nexus/Modules` і `app/Nexus/Plugins`, публікує ресурси
(`config/nexus.php`, `public/nexus`, `public/packages`, `resources/js/nexus`, переклади), виконує міграції,
створює permissions і реєструє всі знайдені модулі (`nexus:module:install`).
Також створює символічне посилання `public/storage` (`php artisan storage:link`): воно потрібне файловому менеджеру elFinder (вибір зображень і відео) — без нього конектор повертає `errNoVolumes`.

Також команда публікує тему адмінки
(`resources/css/nexus-theme.css`, `resources/js/nexus-theme.js`, тег `nexus-theme`), додає
`@import './nexus-theme.css';` у `resources/css/app.css`, `import './nexus-theme.js';` у
`resources/js/app.js` та `alpinejs`, `@alpinejs/collapse` у `package.json` (лише те, чого бракує).
Після цього виконайте `npm install && npm run build` (крок 5). Без цього адмінка відображається без стилів.
Шаблон адмінки очікує `resources/css/app.css` і `resources/js/app.js` як Vite-входи та Tailwind CSS v4
(`@tailwindcss/vite`). Повторна публікація теми (наприклад, після оновлення пакета):
`php artisan vendor:publish --tag=nexus-theme --force`.

Оновлення бібліотеки: `php artisan nexus:update`.

### Які модулі підхоплює пакет

Сама лише папка в `app/Nexus/Modules` модуль не активує. У консолі (`php artisan ...`) і в браузері пакет
підключає міграції, routes, views, переклади, іконки та команди **лише для встановлених і ввімкнених модулів**
(запис у `nexus_modules` з `is_enabled = 1`). Тому:

- папка модуля, який не встановлено (`php artisan nexus:module:install {name}`), ігнорується
  `php artisan migrate` та рештою консолі;
- вимкнений модуль теж ігнорується: його міграції не виконуються, доки модуль не ввімкнуть. Якщо його таблиці
  потрібні іншим модулям (зовнішні ключі), `migrate` на чистій базі може впасти;
- `php artisan migrate:fresh` на порожній базі створює лише таблиці пакета: таблиці модулів з'являються після
  встановлення модулів (`nexus:module:install`, який мігрує модуль за його власним шляхом).

## Залежності модулів і типові помилки установки

Повністю прибрати залежності між модулями неможливо: `Order` потребує `ShopProduct`, `Wishlist` — каталог,
`Book` — `Author` тощо. Залежності оголошуються в `#[Module(requires: [...])]` і є **суто декларативними** —
вони не блокують установку. Тому модуль, встановлений без потрібних йому модулів, може завершитись помилкою
вже під час роботи. Починаючи з цієї версії `nexus:module:install` друкує попередження
(`Module X requires modules that are not installed/enabled: ...`) і пише його в лог; установка при цьому триває.

Ставте модулі від базових до залежних: спочатку модулі, від яких залежать інші, потім залежні.
Перед установкою перегляньте розділ «Requires» у `README` модуля.

| Симптом | Імовірна причина | Що робити |
| --- | --- | --- |
| `Class "App\Nexus\Modules\X\…" not found` | Модуль посилається на клас модуля `X`, якого немає | Встановіть (опублікуйте й `nexus:module:install X`) модуль `X` |
| `Route [name] not defined` | Вью чи редирект використовує маршрут іншого модуля (наприклад, `shop.home`, `account.orders`) | Встановіть модуль, що визначає цей маршрут, або замініть посилання на власний маршрут |
| `Table '…' doesn't exist` | Міграції виконані не в тому порядку або не виконані | `php artisan migrate:status`, потім `php artisan migrate`; спершу ставте модуль-залежність |
| `Duplicate column` / `table already exists` | Міграцію модуля перештамповано (`--force`) після її виконання | Не використовуйте `--force` для модуля з виконаною міграцією; видаліть дублікат файлу міграції |
| Модуль опублікований, але його немає в адмінці | Немає запису в `nexus_modules` | `php artisan nexus:module:install <Name>` |
| `Call to undefined method App\Models\User::…()` | Стандартна модель `User` без трейтів модулів | Опублікуйте модуль `User` (stub замінює модель) або злийте з `src/AppStubs/User.php.stub` |
| `Unable to locate file in Vite manifest` | Сторінка підключає JS/CSS, якого немає у вашому проєкті | Додайте вхід у `vite.config.js` і виконайте `npm run build`, або приберіть підключення |
| Сторінка без стилів | Не зібрано фронтенд або не підключено тему | `npm install && npm run build`, перевірте імпорти `nexus-theme` |
| `Class "Laravel\Socialite\…" not found` | Не встановлено пакет Composer, потрібний модулю | `composer require` пакета з розділу «Requirements» у `README` модуля |

Якщо після виправлення модуль усе ще не працює, перевірте `storage/logs/laravel.log` і виконайте
`php artisan nexus:module:clear`, а потім `php artisan optimize:clear`.
### Livewire-компоненти модулів

Пакет автоматично реєструє Livewire-компоненти модулів: кожен клас із теки `Livewire/*.php` встановленого
модуля реєструється як `nexus-{kebab-case імені класу}` (наприклад, `Livewire\MenuItemsManager` →
`<livewire:nexus-menu-items-manager>`). Додавати `Livewire::component(...)` у `AppServiceProvider` не потрібно.
Клас має наслідувати `Livewire\Component`.
### Глобальний middleware модуля

Модуль, якому потрібно діяти до маршрутизації (наприклад, `Redirect`), оголошує свій middleware в атрибуті:

```php
#[Module(name: 'redirect', globalMiddleware: [RedirectMiddleware::class])]
```

Пакет сам додає його в глобальний стек HTTP-ядра, поки модуль встановлений і ввімкнений. Додавати `append(...)` у `bootstrap/app.php` не потрібно. Для консольних команд middleware не реєструється.

### Модулі лише з налаштуваннями

Модуль, що зберігає тільки значення `#[Setting]` і не має записів (наприклад, `Analytics`), оголошується як `#[Module(name: 'analytics', settingsOnly: true)]`. Його сторінка списку перенаправляє на екран налаштувань, тож пункт меню одразу відкриває форму. Підписи й підказки налаштувань можуть бути ключами перекладу (`label: 'analytics::translate.settings.ga4'`).

### Модулі, що потребують додаткових пакетів Composer

Пакет не підтягує залежності окремих модулів: їх встановлюють вручну **до** `nexus:module:install`. Що саме потрібно, описано в розділі «Requires» `README` кожного модуля. Без потрібного пакета установка модуля падає з `Class "..." not found`.

| Модуль | Пакет Composer | Додатково |
| --- | --- | --- |
| `ActivityLog` | `spatie/laravel-activitylog:^5.1` | Опублікувати лише конфіг (`--tag=activitylog-config`, **без міграцій**) і вказати `activity_model` модуля |
| `SocialAuth` | `laravel/socialite` | Ключі провайдерів у `config/services.php`, `config('auth.social_providers')` |
| `Backup` | `spatie/db-dumper:^4.1` | Утиліта `mysqldump`/`pg_dump` (шлях у `config/backup.php` або `dump.dump_binary_path`), воркер черги для «Run now» |

Також `nodex/nexus` підтримує Laravel 11, 12 і 13 (`illuminate/support: ^11.0 || ^12.0 || ^13.0`).

## Модулі

Модулі до пакету можна знайти на сайті проєкту:
👉 [https://www.nexus-cms.shop/](https://www.nexus-cms.shop/)

Run `php artisan nexus:module:install {name}` - install module (якщо не вказувати ім'я — встановить усі модулі)

Установка токена доступу до приватного репозиторію
composer config --global github-oauth.github.com YOUR_TOKEN
Або введіть токен при запиті під час установки пакету.

Розробка добавлення тегу версійності
git tag -a v1.0.0 -m "Stable release 1.0.0"

Перевірка якості коду має бути встановлений пакет https://github.com/larastan/larastan
php vendor\bin\phpstan analyse

Приклад налаштувань Vite
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
plugins: [
laravel({
input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/nexus/nexus.js'],
refresh: true,
}),
tailwindcss(),
vue(),
],
server: {
host: '127.0.0.1',
port: 5173,
cors: true
}
});

пакети для npm
"devDependencies": {
"@tailwindcss/vite": "^4.0.0",
"@vitejs/plugin-vue": "^6.0.1",
"axios": "^1.11.0",
"concurrently": "^9.0.1",
"laravel-vite-plugin": "^2.0.0",
"tailwindcss": "^4.0.0",
"vite": "^7.1.3"
},
"dependencies": {
"laravel-echo": "^2.2.0",
"pusher-js": "^8.4.0",
"vue": "^3.5.18",
"vue-select2": "^0.2.6"
}



npm install vue@3 axios vue-select2 laravel-echo pusher-js
