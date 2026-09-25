<?php

namespace Nodex\Nexus\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CheckAuthModuleInstalled
{
    private const HINT = 'Nexus admin panel is unavailable: the Auth module is not published. Run: php artisan nexus:default_module:publish --module=Auth,User,Permission,Role';

    /**
     * Without the Auth module there is no 'login' route, so the 'auth'
     * middleware would fail with RouteNotFoundException (500). Respond with
     * a plain 404 instead; the setup hint is logged, and shown only in debug.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! is_dir(app_path('Nexus/Modules/Auth'))) {
            Log::warning(self::HINT);

            if (config('app.debug')) {
                return response(self::HINT, 404);
            }

            abort(404);
        }

        return $next($request);
    }
}
