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

## Starter modules (Auth, User, Permission, Role)

The package ships with 4 base modules — without them there's nothing to log into the admin panel with
(`Auth` — the login form, `Permission`/`Role` — wrappers around
`spatie/laravel-permission`, `User` — the Eloquent model that Laravel
authenticates against). They live in `src/Modules/{Auth,User,Permission,Role}` of the package under
the namespace `Nodex\Nexus\Modules\{Name}` and are published into the project with the command:

1. First publish and migrate the base spatie tables:
   ```
   php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
   ```
   
```
php artisan nexus:default_module:publish --module=Auth,User,Permission,Role
```

(without `--module` it publishes all starter modules at once). The command itself rewrites
the namespace `Nodex\Nexus\Modules\{Name}` → `App\Nexus\Modules\{Name}` in
the copied files. If a module with that name already exists in
`app/Nexus/Modules`, publishing is skipped (it does not overwrite) — add
`--force` to overwrite deliberately.

**The `App\Models\User` model** is a special case: it doesn't live inside
any module (Laravel always expects the auth model specifically at the path
`app/Models/User.php`), so the same publish command additionally copies
`src/AppStubs/User.php.stub` → `app/Models/User.php` whenever `User` is among the modules
being published (with the same rule: it doesn't overwrite an existing file without
`--force`). This is intentionally a **minimal** stub — only what these 4 modules
actually use (`UserModelTrait` → `HasRoles`, password-reset/email-confirmation
notifications, the `addresses()` relation to `UserAddress` from
this same module). If the project later gains `Cart`/`Wishlist`/
`ActivityLog` modules — the corresponding relations/traits are added to `app/Models/User.php`
manually; at that point it's no longer a package file, but a regular application file.

⚠️ **Order for `Permission`/`Role`**: their own migrations
(`add_display_field`) run `Schema::table('permissions'/'roles', ...)` —
meaning they require the base tables from `spatie/laravel-permission` to already
exist. Laravel runs pending migrations sorted by filename, not
by logical dependency, so `nexus:default_module:publish` **re-stamps**
all of the module's migrations with the current publish timestamp itself (the same
trick Laravel uses to publish its own package migrations) — this guarantees they
sort *after* everything already sitting in `database/migrations`,
including the just-published spatie migration. This works, but it implies a
mandatory order of operations:


2. **Only after this** publish `Permission`/`Role` (`nexus:default_module:publish --module=Permission,Role`) — the module's timestamp is set at the moment of publishing, so if the module is published BEFORE the spatie migration, the problem returns.
3. `php artisan migrate`.

If a module has already been published and its migration has already run —
a `--force` republish **re-stamps the file again**, and Laravel will attempt
to run the "new" migration again (`Duplicate column`). Do not use `--force`
on a module whose migration is already in the `Ran` status.

Run `php artisan nexus:install` - install library

Run `php artisan nexus:update` - update library

## Modules

Additional modules for the package can be found on the project website:
👉 [https://www.nexus-cms.shop/](https://www.nexus-cms.shop/)

Run `php artisan nexus:module:install {name}` - install module (omit name to install all modules)

Setting up an access token for the private repository
composer config --global github-oauth.github.com YOUR_TOKEN
Or enter the token when prompted during package installation.

Adding a version tag during development
git tag -a v1.0.0 -m "Stable release 1.0.0"

Code quality check requires the package https://github.com/larastan/larastan to be installed
php vendor\bin\phpstan analyse

Example Vite configuration
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

npm packages
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
