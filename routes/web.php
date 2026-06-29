<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\ThemeController;
use App\Http\Controllers\Administration\UserManagementController;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::post('/themes/select', [ThemeController::class, 'select'])->middleware(['auth'])->name('themes.select');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'verified'])->prefix('administracion')->name('administracion.')->group(function () {
    Route::get('/empleados', [UserManagementController::class, 'employees'])
        ->name('empleados.index');

    Route::post('/empleados', [UserManagementController::class, 'storeEmployee'])
        ->name('empleados.store');

    Route::get('/clientes', [UserManagementController::class, 'clients'])
        ->name('clientes.index');

    Route::post('/clientes', [UserManagementController::class, 'storeClient'])
        ->name('clientes.store');

    Route::get('/administradores', [UserManagementController::class, 'administrators'])
        ->name('administradores.index');

    Route::post('/administradores', [UserManagementController::class, 'storeAdministrator'])
        ->name('administradores.store');

    Route::get('/usuarios/buscar', [UserManagementController::class, 'search'])
        ->name('usuarios.buscar');

    Route::patch('/usuarios/{user}', [UserManagementController::class, 'update'])
        ->name('usuarios.update');

    Route::delete('/usuarios/seleccionados', [UserManagementController::class, 'bulkDestroy'])
        ->name('usuarios.bulkDestroy');

    Route::delete('/usuarios/{user}', [UserManagementController::class, 'destroy'])
        ->name('usuarios.destroy');

    Route::get('/usuarios/export/excel', [UserManagementController::class, 'exportExcel'])
        ->name('usuarios.export.excel');

    Route::get('/usuarios/export/pdf', [UserManagementController::class, 'exportPdf'])
        ->name('usuarios.export.pdf');
});

require __DIR__ . '/auth.php';
