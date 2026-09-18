<?php

namespace Nodex\Nexus\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Nodex\Nexus\Enums\AdminPanelPermissionEnum;

class NexusAdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->user()->hasPermissionTo(AdminPanelPermissionEnum::ADMIN_PANEL->value)) {
            return $next($request);
        }
        else {
            return redirect()->route('logout')->withErrors([__('nexus::translate.Prmision denied')]);
        }

    }
}
