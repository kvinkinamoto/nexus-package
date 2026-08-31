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

Run `php artisan nexus:install` - install library

Run `php artisan nexus:update` - update library

Run `php artisan nexus:module:install {name}` - install module (miss name - all modules)






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
