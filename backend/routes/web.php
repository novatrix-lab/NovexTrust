<?php

declare(strict_types=1);

use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\LocaleController;
use App\Livewire\Dashboard;
use App\Livewire\Entities\Index as EntitiesIndex;
use App\Livewire\Entities\Show as EntityShow;
use App\Livewire\ImportEntities;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

// Locale switch (works for guests on the login page and authenticated users).
Route::post('/locale', [LocaleController::class, 'update'])->name('locale.update');

// Guest (session) auth for the cockpit. Login is rate-limited.
Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'show'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// Cockpit: authenticated + tenant-scoped (the `tenant` middleware sets the
// tenant context from the logged-in user, as on the API).
Route::middleware(['auth', 'tenant'])->group(function (): void {
    Route::get('/dashboard', Dashboard::class)->name('dashboard');
    Route::get('/entities', EntitiesIndex::class)->name('entities.index');
    Route::get('/entities/import', ImportEntities::class)->name('entities.import');
    Route::get('/entities/{id}', EntityShow::class)->name('entities.show');
});
