<?php
use Illuminate\Support\Facades\Route;
use Nodex\Nexus\Http\Controllers\NexusApiController;
use Nodex\Nexus\Http\Controllers\WidgetApiController;

Route::middleware(config('nexus.api_middleware',[]))->prefix(config('nexus.api_prefix'))->group(function ()
{
    // Registered before the {module}/action/{action?} catch-all below on
    // purpose — a generic wildcard route must never get a chance to shadow
    // a named resource route registered after it.
    Route::get('/widgets', [WidgetApiController::class, 'index'])->name('api.nexus.widgets.index');
    Route::get('/widgets/{key}', [WidgetApiController::class, 'show'])->name('api.nexus.widgets.show');

    Route::post("/{module}/bulk/{action}", [NexusApiController::class, 'bulkAction'])->name("api.nexus.module.bulk");
    Route::any("/{module}/action/{action}/{id?}", [NexusApiController::class, 'action'])->name("api.nexus.module.action");
//            Route::post("/{module}/post-action/{action}", [NexusController::class, 'postAction'])->name("nexus.module.post_action");
});
