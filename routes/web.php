<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\ThemeController;
use App\Http\Controllers\Administration\UserManagementController;
use App\Http\Controllers\Administration\AuditLogController;
use App\Http\Controllers\Product\CategorieController;
use App\Http\Controllers\Product\ProductController;
use App\Http\Controllers\Product\SubCategorieController;

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

    Route::get('/bitacora', [AuditLogController::class, 'index'])
        ->name('bitacora.index');

    Route::get('/bitacora/export/csv', [AuditLogController::class, 'exportCsv'])
        ->name('bitacora.export.csv');

    Route::get('/bitacora/export/pdf', [AuditLogController::class, 'exportPdf'])
        ->name('bitacora.export.pdf');

    Route::get('/bitacora/export/txt', [AuditLogController::class, 'exportTxt'])
        ->name('bitacora.export.txt');

    Route::get('/bitacora/data', [AuditLogController::class, 'data'])
        ->name('bitacora.data');
});
Route::middleware([
    'auth',
    'verified',
    'role:Master|Administrador|Mesero',
])
    ->prefix('productos')
    ->name('products.')
    ->group(function () {
        Route::get('/', [ProductController::class, 'index'])->name('index');
        Route::get('/ajax/listar', [ProductController::class, 'ajax'])->name('ajax');
        Route::get('/ajax/subcategorias', [SubCategorieController::class, 'ajax'])->name('sub-categories.ajax');

        Route::get('/exportar', [ProductController::class, 'export'])->name('export');
        Route::get('/exportar/excel', [ProductController::class, 'exportExcel'])->name('export.excel');
        Route::get('/exportar/pdf', [ProductController::class, 'exportPdf'])->name('export.pdf');
        Route::get('/exportar/txt', [ProductController::class, 'exportTxt'])->name('export.txt');

        Route::post('/', [ProductController::class, 'store'])->name('store');
        Route::post('/{item}/actualizar', [ProductController::class, 'update'])->name('update');
        Route::delete('/{item}', [ProductController::class, 'destroy'])->name('destroy');

        Route::get('/categorias', [CategorieController::class, 'index'])->name('categories.index');
        Route::get('/categorias/ajax/listar', [CategorieController::class, 'ajax'])->name('categories.ajax');

        Route::get('/categorias/exportar', [CategorieController::class, 'export'])->name('categories.export');
        Route::get('/categorias/exportar/excel', [CategorieController::class, 'exportExcel'])->name('categories.export.excel');
        Route::get('/categorias/exportar/pdf', [CategorieController::class, 'exportPdf'])->name('categories.export.pdf');
        Route::get('/categorias/exportar/txt', [CategorieController::class, 'exportTxt'])->name('categories.export.txt');

        Route::post('/categorias', [CategorieController::class, 'store'])->name('categories.store');
        Route::put('/categorias/{category}', [CategorieController::class, 'update'])->name('categories.update');
        Route::delete('/categorias/{category}', [CategorieController::class, 'destroy'])->name('categories.destroy');

        Route::post('/subcategorias', [SubCategorieController::class, 'store'])->name('sub-categories.store');
        Route::post('/subcategorias/{subCategory}/actualizar', [SubCategorieController::class, 'update'])->name('sub-categories.update');
        Route::delete('/subcategorias/{subCategory}', [SubCategorieController::class, 'destroy'])->name('sub-categories.destroy');
    });
require __DIR__ . '/auth.php';
