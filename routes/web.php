<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web routes
|--------------------------------------------------------------------------
| All user-facing routes are Persian/RTL (views + lang/fa). Public menu
| routes live on the menu domain/path (Phase 3).
*/

// Marketing / guest pages (full landing in Phase 8)
Route::view('/', 'home')->name('home');
Route::view('/terms', 'pages.terms')->name('terms');
Route::view('/privacy', 'pages.privacy')->name('privacy');

// Authentication (phone + OTP)
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login/otp', [LoginController::class, 'sendOtp'])->name('login.otp.send');
    Route::get('/login/verify', [LoginController::class, 'showVerify'])->name('login.verify');
    Route::post('/login/verify', [LoginController::class, 'verify'])->name('login.verify.submit');
    Route::post('/login/resend', [LoginController::class, 'resend'])->name('login.resend');

    Route::get('/login/password', [LoginController::class, 'showPasswordLogin'])->name('login.password');
    Route::post('/login/password', [LoginController::class, 'passwordLogin'])->name('login.password.submit');

    Route::post('/password/forgot', [LoginController::class, 'forgotPassword'])->name('password.forgot');
    Route::get('/password/reset', [PasswordController::class, 'showResetForm'])->name('password.reset.form');
    Route::post('/password/reset', [PasswordController::class, 'reset'])->name('password.reset');
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// Owner panel
Route::middleware(['auth', 'tenant'])->prefix('panel')->name('panel.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/onboarding', [\App\Http\Controllers\OnboardingController::class, 'show'])->name('onboarding');
    Route::post('/onboarding', [\App\Http\Controllers\OnboardingController::class, 'store'])->name('onboarding.store');
    Route::post('/onboarding/slug-check', [\App\Http\Controllers\OnboardingController::class, 'checkSlug'])->name('onboarding.slug-check');
    Route::post('/password', [PasswordController::class, 'update'])->name('password.update');
});
