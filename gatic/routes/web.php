<?php

use App\Livewire\Admin\Users\UserForm;
use App\Livewire\Admin\Users\UsersIndex;
use App\Livewire\Dev\LivewireSmokeTest;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return redirect()->route('login');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'active'])->name('dashboard');

Route::middleware(['auth', 'active', 'can:users.manage'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/users', UsersIndex::class)->name('users.index');
        Route::get('/users/create', UserForm::class)->name('users.create');
        Route::get('/users/{user}/edit', UserForm::class)->name('users.edit');
    });

if (app()->environment(['local', 'testing'])) {
    Route::get('/dev/livewire-smoke', LivewireSmokeTest::class)
        ->middleware(['auth', 'active'])
        ->name('dev.livewire-smoke');
}

// MVP: Profile management deshabilitado - Story 1.3 scope = "solo login/logout"
// Habilitar en story futura cuando se requiera gestion de perfil
// Route::middleware('auth')->group(function () {
//     Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
//     Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
//     Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
// });

require __DIR__.'/auth.php';
