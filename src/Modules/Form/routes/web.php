<?php

use Illuminate\Support\Facades\Route;
use Nodex\Nexus\Modules\Form\Http\Controllers\FormSubmissionController;

Route::middleware(['web', 'throttle:10,1'])->group(function () {
    Route::post('/forms/{slug}/submit', [FormSubmissionController::class, 'store'])->name('nexus.form.submit');
});
