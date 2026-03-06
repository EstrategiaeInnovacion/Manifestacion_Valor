<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordRecoveryController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    // Registro deshabilitado: solo el administrador puede crear usuarios
    Route::get('register', fn() => redirect()->route('login'))->name('register');

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    // ── Recuperación de contraseña por código de verificación ──────────────

    // Paso 1: ingresar usuario o correo
    Route::get('forgot-password', [PasswordRecoveryController::class, 'requestForm'])
        ->name('password.request');

    Route::post('forgot-password', [PasswordRecoveryController::class, 'sendCode'])
        ->name('password.email');

    // Paso 2: verificar código
    Route::get('verify-code', [PasswordRecoveryController::class, 'verifyForm'])
        ->name('password.verify');

    Route::post('verify-code', [PasswordRecoveryController::class, 'verifyCode'])
        ->name('password.verify.store');

    Route::post('resend-code', [PasswordRecoveryController::class, 'resendCode'])
        ->name('password.resend');

    // Paso 3: nueva contraseña
    Route::get('set-password', [PasswordRecoveryController::class, 'resetForm'])
        ->name('password.reset');

    Route::post('set-password', [PasswordRecoveryController::class, 'savePassword'])
        ->name('password.store');
});

Route::middleware('auth')->group(function () {
    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
