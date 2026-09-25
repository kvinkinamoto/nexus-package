## <h2 style="color:#ba363f">Installation</h2>

Requirements: PHP ^8.3, Laravel 13, Vite + Tailwind CSS v4 (`@tailwindcss/vite`), Node.js/npm.

### Installation order

1. Install the package:
   ```
   composer require nodex/nexus
   ```
2. Publish the base `spatie/laravel-permission` tables (they must exist **before** the `Permission`/`Role` modules are published):
   ```
   php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
   ```
3. Publish the starter modules:
   ```
   php artisan nexus:default_module:publish --module=Auth,User,Permission,Role
   ```
4. Install the library (resources, admin theme, migrations, module registration):
   ```
   php artisan nexus:install
   ```
5. Build the admin frontend:
   ```
   npm install && npm run build
   ```
6. Create the super administrator:
   ```
   php artisan nexus:create:superadmin
   ```
7. Open `/admin` (the prefix is set by the `ADMIN_PREFIX` variable).

> If the starter modules were published **after** `nexus:install` had been run, they are not registered in the
> admin panel (a module shows up in the admin only when it has a row in the `nexus_modules` table). Run
> `php artisan nexus:module:install` (without a name — every published module) or
> `php artisan nexus:module:install User` for a single module.

⚠️ Without the base modules published, any attempt to access `/admin` results in a plain 404 (instead of a 500).
This is the expected behavior. The hint on how to publish the modules is always written to the log
(`storage/logs`) and is shown on the page only when `APP_DEBUG=true`.

## Starter modules (Auth, User, Permission, Role)

The package ships with 4 base modules — without them there's nothing to log into the admin panel with
(`Auth` — the login form, `Permission`/`Role` — wrappers around
`spatie/laravel-permission`, `User` — the Eloquent model that Laravel
authenticates against). They live in `src/Modules/{Auth,User,Permission,Role}` of the package under
the namespace `Nodex\Nexus\Modules\{Name}` and are published with the command from step 3
(without `--module` it publishes all starter modules at once). The command itself rewrites
the namespace `Nodex\Nexus\Modules\{Name}` → `App\Nexus\Modules\{Name}` in
the copied files. If a module with that name already exists in
`app/Nexus/Modules`, publishing is skipped (it does not overwrite) — add
`--force` to overwrite deliberately.

The `Auth` pages (login, registration, password recovery) are self-contained: they use `resources/css/app.css`,
`resources/js/app.js` and Livewire (which ships Alpine), with no separate storefront JS.
Social login (`SocialAuth`) and the customer account area (`Account`) are separate optional modules and are not part
of the starter set. The `Cart`/`Wishlist` modules are optional: `Auth`/`User` work without them.

**The `App\Models\User` model** is a special case: it doesn't live inside
any module (Laravel always expects the auth model specifically at the path
`app/Models/User.php`), so the same publish command additionally copies
`src/AppStubs/User.php.stub` → `app/Models/User.php` whenever `User` is among the modules
being published (an existing file is not overwritten without `--force`, with one exception: Laravel's stock
`app/Models/User.php` — the one that doesn't use `UserModelTrait` — is backed up to `app/Models/User.php.bak`
and replaced by the stub; without this replacement `assignRole()` and the admin login fail with
`Call to undefined method App\Models\User::assignRole()`). If your `User` model is already customised, merge
it manually with `src/AppStubs/User.php.stub` (or copy the stub over it:
`copy vendor\nodex\nexus\src\AppStubs\User.php.stub app\Models\User.php`). This is intentionally a **minimal** stub — only what these 4 modules
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
including the just-published spatie migration. That is why step 2 (spatie) must
run **before** step 3 — the module's timestamp is set at the moment of publishing, so if the module
is published BEFORE the spatie migration, the problem returns.

If a module has already been published and its migration has already run —
a `--force` republish **re-stamps the file again**, and Laravel will attempt
to run the "new" migration again (`Duplicate column`). Do not use `--force`
on a module whose migration is already in the `Ran` status.

## What `nexus:install` does

`php artisan nexus:install` creates `app/Nexus/Modules` and `app/Nexus/Plugins`, publishes the resources
(`config/nexus.php`, `public/nexus`, `public/packages`, `resources/js/nexus`, translations), runs the migrations,
creates the permissions and registers every module it finds (`nexus:module:install`).

It also publishes the admin theme
(`resources/css/nexus-theme.css`, `resources/js/nexus-theme.js`, tag `nexus-theme`), adds
`@import './nexus-theme.css';` to `resources/css/app.css`, `import './nexus-theme.js';` to
`resources/js/app.js` and `alpinejs`, `@alpinejs/collapse` to `package.json` (only what is missing).
Then run `npm install && npm run build` (step 5). Without this the admin panel renders without styles.
The admin layout expects `resources/css/app.css` and `resources/js/app.js` as Vite entries and Tailwind CSS v4
(`@tailwindcss/vite`). To re-publish the theme (e.g. after a package update):
`php artisan vendor:publish --tag=nexus-theme --force`.

Update the library: `php artisan nexus:update`.
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
