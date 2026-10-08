<?php

namespace Nodex\Nexus\Support;

use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRoutes;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationViewPath;
use Mcamara\LaravelLocalization\Middleware\LocaleCookieRedirect;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;

/**
 * Route-group attributes that put public pages under the mcamara/laravel-localization locale prefix
 * (`/en/login`; the default locale stays unprefixed when the host config says `hideDefaultLocaleInURL`).
 * Without a `laravellocalization` config in the host it degrades to plain `web`, so a single-language
 * application keeps working unchanged. API routes are never localized.
 *
 *   Route::group(LocalizedRoutes::attributes(), fn () => ...);                 // pages (GET)
 *   Route::group(LocalizedRoutes::attributes(redirects: false), fn () => ...); // form posts
 *
 * `redirects: false` skips the locale redirect middleware: a POST must never be bounced to another locale
 * URL (the session may remember a different language than the page the form was rendered on).
 */
class LocalizedRoutes
{
    /**
     * @param  array<int, string>  $middleware  extra middleware, applied after `web`
     * @return array{prefix?: string, middleware: array<int, string>}
     */
    public static function attributes(array $middleware = [], bool $redirects = true): array
    {
        $base = array_merge(['web'], $middleware);

        if (! class_exists(LaravelLocalization::class) || ! config('laravellocalization.supportedLocales')) {
            return ['middleware' => $base];
        }

        // Registered here (idempotent) so the result does not depend on which module's routes load first.
        Route::aliasMiddleware('localize', LaravelLocalizationRoutes::class);
        Route::aliasMiddleware('localizationRedirect', LaravelLocalizationRedirectFilter::class);
        Route::aliasMiddleware('localeSessionRedirect', LocaleSessionRedirect::class);
        Route::aliasMiddleware('localeCookieRedirect', LocaleCookieRedirect::class);
        Route::aliasMiddleware('localeViewPath', LaravelLocalizationViewPath::class);

        return [
            'prefix' => LaravelLocalization::setLocale(),
            'middleware' => array_merge($base, $redirects
                ? ['localeSessionRedirect', 'localizationRedirect', 'localeCookieRedirect', 'localeViewPath']
                : ['localeViewPath']),
        ];
    }
}
