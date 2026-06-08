<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\ConsentController;
use App\Http\Controllers\Api\DeadlineController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

// All API route names are namespaced under `api.` so they never collide with
// the web cockpit's route names (e.g. both have a `login`/`logout`).
Route::name('api.')->group(function (): void {
    // Public auth endpoints (rate-limited against brute force).
    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:6,1')->name('register');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:6,1')->name('login');

    // Authenticated, tenant-scoped endpoints. `tenant` runs after `auth:sanctum`
    // to establish the current tenant from the authenticated user.
    Route::middleware(['auth:sanctum', 'tenant'])->group(function (): void {
        Route::get('/user', [UserController::class, 'me'])->name('user.me');
        Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/{id}', [UserController::class, 'show'])->name('users.show');

        // Encrypted document vault (M5).
        Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
        Route::post('/documents', [DocumentController::class, 'store'])->name('documents.store');
        Route::get('/documents/{id}', [DocumentController::class, 'show'])->name('documents.show');
        Route::get('/documents/{id}/download', [DocumentController::class, 'download'])->name('documents.download');
        Route::post('/documents/{id}/confirm', [DocumentController::class, 'confirm'])->name('documents.confirm');

        // Deadlines (read-only; cockpit data source) — M7.
        Route::get('/deadlines', [DeadlineController::class, 'index'])->name('deadlines.index');

        // Consent + audit trail (M10).
        Route::get('/consents', [ConsentController::class, 'index'])->name('consents.index');
        Route::post('/consents', [ConsentController::class, 'store'])->name('consents.store');
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    });
});
