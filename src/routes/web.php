<?php
use Nodex\Nexus\Http\Controllers\NexusController;
use Illuminate\Support\Facades\Route;


Route::middleware(config('nexus.admin_middleware'))->prefix(config('nexus.admin_prefix'))->group(function () {
    Route::get('/', [NexusController::class, 'admin'])->name('nexus.admin');

    Route::post("/{module}/bulk/{action}", [NexusController::class, 'bulkAction'])->name("nexus.module.bulk");
    Route::post("/preferences/table-columns/{module}", [NexusController::class, 'saveTableColumns'])->name("nexus.preferences.table-columns");
    Route::post("/preferences/filters/{module}", [NexusController::class, 'saveDynamicFilters'])->name("nexus.preferences.filters");
    Route::post("/preferences/dashboard-layout", [NexusController::class, 'saveDashboardLayout'])->name("nexus.preferences.dashboard-layout");
    Route::get("/widgets/{key}/card", [NexusController::class, 'widgetCard'])->name("nexus.widgets.card");
    Route::get("/search", [NexusController::class, 'globalSearch'])->name("nexus.search");
    Route::get("/notifications", [NexusController::class, 'notifications'])->name("nexus.notifications.index");
    Route::post("/notifications/read-all", [NexusController::class, 'markAllNotificationsRead'])->name("nexus.notifications.readAll");
    Route::post("/notifications/{id}/read", [NexusController::class, 'markNotificationRead'])->name("nexus.notifications.markAsRead");
    Route::delete("/preferences/filters/{module}", [NexusController::class, 'removeDynamicFilters'])->name("nexus.preferences.filters.destroy");
    Route::get("/async-relation/{module}/{relation}", [NexusController::class, 'asyncRelation'])->name("nexus.async-relation");
    Route::get("/relation-search/{module}/{relation}", [\Nodex\Nexus\Http\Controllers\AjaxController::class, 'relationSearch'])->name("nexus.relation-search");
    Route::any("/{module}/action/{action}/{id?}", [NexusController::class, 'action'])->name("nexus.module.action");
    //            Route::post("/{module}/post-action/{action}", [NexusController::class, 'postAction'])->name("nexus.module.post_action");
});

// Deliberately outside the admin_middleware group (no NexusAdminMiddleware):
// while impersonating a plain user, that user by design lacks ADMIN_PANEL,
// so a route requiring it would make "stop impersonating" unreachable —
// the one action that must always work regardless of the current session's
// own permissions.
Route::middleware(['web', 'auth'])->prefix(config('nexus.admin_prefix'))->group(function () {
    Route::post("/stop-impersonating", [NexusController::class, 'stopImpersonating'])->name("nexus.impersonate.stop");
});
