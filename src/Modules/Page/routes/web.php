<?php

use Illuminate\Support\Facades\Route;
use Nodex\Nexus\Modules\Page\Http\Controllers\PageController;

Route::middleware(['web'])->group(function () {
    Route::get('/pages/{slug}', [PageController::class, 'show'])->name('nexus.page.show');
});
