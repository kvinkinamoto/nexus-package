<?php

use App\Nexus\Modules\Auth\Http\Controllers\AuthController;
use App\Nexus\Modules\Auth\Http\Controllers\EmailVerificationController;
use App\Nexus\Modules\Auth\Http\Controllers\PasswordResetController;
use Illuminate\Support\Facades\Route;
use Nodex\Nexus\Support\LocalizedRoutes;

// Pages: under the locale prefix when the host app configures mcamara/laravel-localization (/en/login).
Route::group(LocalizedRoutes::attributes(), function () {
    Route::get('/login', [AuthController::class, 'loginPage'])->name('login');
    Route::get('/register', [AuthController::class, 'registerPage'])->name('shop.register');
    Route::get('/forgot-password', [AuthController::class, 'forgotPasswordPage'])->name('shop.forgot-password');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
});

// Form posts: same prefix (so route() in a localized page points at the right URL) but no locale redirects —
// a POST must not be bounced to another language's URL.
Route::group(LocalizedRoutes::attributes(redirects: false), function () {
    // throttle:5,1 — 5 attempts/minute, same limiter style already used
    // below for verification-email resend. Neither login endpoint had any
    // brute-force protection before this.
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('login.submit');

    Route::post('/register', [AuthController::class, 'register'])->name('register.submit');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])->name('password.email');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])->name('password.update');
});

Route::middleware(['web'])->group(function () {
    // JSON login for the SPA — same AuthService::login() as the web form
    // above, only the response shape differs.
    Route::post('/api/login', [AuthController::class, 'apiLogin'])->middleware('throttle:5,1');
});

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/logout', [AuthController::class, 'logout'])->name('logout');
});
// Everything below is JSON, not a page — auth:sanctum instead of the plain
// session guard, same reasoning as EnsureFrontendRequestsAreStateful's
// docblock in bootstrap/app.php.
Route::middleware(['web', 'auth:sanctum'])->group(function () {
    Route::post('/api/logout', [AuthController::class, 'apiLogout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::get('/verify-email', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/verify-email/{id}/{hash}', [EmailVerificationController::class, 'verify'])->middleware('signed')->name('verification.verify');
    Route::post('/verify-email/resend', [EmailVerificationController::class, 'resend'])->middleware('throttle:6,1')->name('verification.send');
});
