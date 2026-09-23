## <h2 style="color:#ba363f">Installation</h2>

Add repository to `composer.json`
```
    "repositories": [
        {
            "type": "vcs",
            "url": "git@github.com:NodexSoft/nexus_core.git"
        }
    ],
    "require": {
        ...
        "nodex/nexus": "dev-main" // Dev Mode
        "nodex/nexus": "^0.0.1" // Production Mode
    }
```

Run `composer install` - add library to project

## Стартові модулі (Auth, User, Permission, Role)

Пакет несе разом із собою 4 базові модулі — без них нема з чим зайти в адмінку
(`Auth` — форма логіну, `Permission`/`Role` — обгортки над
`spatie/laravel-permission`, `User` — Eloquent-модель, на яку авторизується
Laravel). Живуть у `src/Modules/{Auth,User,Permission,Role}` пакета під
неймспейсом `Nodex\Nexus\Modules\{Name}` і публікуються в проєкт командою:

1. Спочатку опублікувати й зміґрувати базові таблиці spatie:
   ```
   php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
   ```
   
```
php artisan nexus:default_module:publish --module=Auth,User,Permission,Role
```

(без `--module` публікує всі стартові модулі одразу). Команда сама переписує
неймспейс `Nodex\Nexus\Modules\{Name}` → `App\Nexus\Modules\{Name}` у
скопійованих файлах. Якщо модуль з такою назвою вже існує в
`app/Nexus/Modules`, публікація пропускається (не перезаписує) — додайте
`--force`, щоб перезаписати свідомо.

**Модель `App\Models\User`** — окремий випадок: вона не лежить всередині
жодного модуля (Laravel завжди чекає auth-модель саме за шляхом
`app/Models/User.php`), тому та сама команда публікації додатково копіює
`src/AppStubs/User.php.stub` → `app/Models/User.php`, коли серед модулів, що
публікуються, є `User` (з тим самим правилом: не перезаписує наявний файл без
`--force`). Це навмисно **мінімальний** stub — тільки те, що реально
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
включно зі щойно опублікованою spatie-міграцією. Working, але звідси
випливає обов'язковий порядок дій:


2. **Тільки після цього** публікувати `Permission`/`Role` (`nexus:default_module:publish --module=Permission,Role`) — таймстемп модуля ставиться в момент публікації, тож якщо опублікувати модуль РАНІШЕ spatie-міграції, проблема повернеться.
3. `php artisan migrate`.

Якщо модуль уже був опублікований і його міграція вже виконалась —
`--force` republish **перештампує файл заново**, і Laravel спробує
виконати "нову" міграцію повторно (`Duplicate column`). Не робіть `--force`
на модулі, чия міграція вже в статусі `Ran`.

Run `php artisan nexus:install` - install library

Run `php artisan nexus:update` - update library

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
// import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
plugins: [
laravel({
input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/nexus/nexus.js'],
refresh: true,
}),
// tailwindcss(),
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
