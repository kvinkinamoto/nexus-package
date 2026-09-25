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

Також команда публікує тему адмінки
(`resources/css/nexus-theme.css`, `resources/js/nexus-theme.js`, тег `nexus-theme`), додає
`@import './nexus-theme.css';` у `resources/css/app.css`, `import './nexus-theme.js';` у
`resources/js/app.js` та `alpinejs`, `@alpinejs/collapse` у `package.json` (лише те, чого бракує).
Після цього виконайте `npm install && npm run build` (крок 5). Без цього адмінка відображається без стилів.
Шаблон адмінки очікує `resources/css/app.css` і `resources/js/app.js` як Vite-входи та Tailwind CSS v4
(`@tailwindcss/vite`). Повторна публікація теми (наприклад, після оновлення пакета):
`php artisan vendor:publish --tag=nexus-theme --force`.

Оновлення бібліотеки: `php artisan nexus:update`.
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
