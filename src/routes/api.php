<?php

use Illuminate\Support\Facades\Route;
use Nodex\Nexus\Http\Controllers\GraphQLController;
use Nodex\Nexus\Http\Controllers\NexusApiController;
use Nodex\Nexus\Http\Controllers\WidgetApiController;

// Deliberately its own top-level route, not nested under api_prefix — GraphQL
// clients expect one fixed endpoint (conventionally /graphql), not one path
// per resource the way the REST wildcard route below is. Needs auth:sanctum
// on top of the plain 'api' group REST uses, since token auth is this
// endpoint's own concern (see Services/GraphQL/SchemaBuilder's docblock).
Route::middleware(config('nexus.graphql_middleware', []))
    ->post(config('nexus.graphql_prefix'), [GraphQLController::class, 'handle'])
    ->name('api.nexus.graphql');

Route::middleware(config('nexus.api_middleware', []))->prefix(config('nexus.api_prefix'))->group(function () {
    // Registered before the {module}/action/{action?} catch-all below on
    // purpose — a generic wildcard route must never get a chance to shadow
    // a named resource route registered after it. Deliberately NOT behind
    // auth:sanctum — WidgetApiController::show() has its own anonymous-vs-
    // authenticated logic (public widgets are meant to be reachable
    // anonymously, and a non-public widget must 404 rather than 401/403 to
    // an anonymous caller, so its existence isn't disclosed). Blanket
    // auth:sanctum here would reject the request before that logic ever ran.
    Route::get('/widgets', [WidgetApiController::class, 'index'])->name('api.nexus.widgets.index');
    Route::get('/widgets/{key}', [WidgetApiController::class, 'show'])->name('api.nexus.widgets.show');

    // The actual data-CRUD surface — this is what needed the same Sanctum
    // token auth as /graphql, not the widgets routes above.
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/{module}/bulk/{action}', [NexusApiController::class, 'bulkAction'])->name('api.nexus.module.bulk');
        Route::any('/{module}/action/{action}/{id?}', [NexusApiController::class, 'action'])->name('api.nexus.module.action');
        //            Route::post("/{module}/post-action/{action}", [NexusController::class, 'postAction'])->name("nexus.module.post_action");
    });
});
