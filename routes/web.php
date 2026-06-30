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
use App\Http\Controllers\Reservation\TableController;
use App\Http\Controllers\Reservation\AdminReservationController;
use App\Http\Controllers\Reservation\ClientReservationController;
use App\Http\Controllers\Order\AdminOrderController;
use App\Http\Controllers\Order\ClientOrderController;

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


Route::middleware(['auth'])->group(function () {
    Route::middleware(['role:Master|Administrador|Mesero'])->group(function () {
        Route::resource('/mesas', TableController::class)
            ->parameters(['mesas' => 'table'])
            ->except(['create', 'show', 'edit'])
            ->names('reservation.tables');

        Route::get('/admin/reservas', [AdminReservationController::class, 'index'])
            ->name('admin.reservations.index');

        Route::get('/admin/reservas/clientes/buscar', [AdminReservationController::class, 'searchClients'])
            ->name('admin.reservations.search-clients');

        Route::get('/admin/reservas/mesas-disponibles/buscar', [AdminReservationController::class, 'availableTables'])
            ->name('admin.reservations.available-tables');

        Route::get('/admin/reservas/mesas-disponibles/ahora', [AdminReservationController::class, 'availableNowTables'])
            ->name('admin.reservations.available-now-tables');

        Route::post('/admin/reservas', [AdminReservationController::class, 'store'])
            ->name('admin.reservations.store');

        Route::put('/admin/reservas/{reservation}', [AdminReservationController::class, 'update'])
            ->name('admin.reservations.update');

        Route::patch('/admin/reservas/{reservation}/estado', [AdminReservationController::class, 'changeState'])
            ->name('admin.reservations.change-state');
    });

    Route::middleware(['role:Cliente'])->group(function () {
        Route::get('/cliente/reservas', [ClientReservationController::class, 'index'])
            ->name('client.reservations.index');

        Route::post('/cliente/reservas', [ClientReservationController::class, 'store'])
            ->name('client.reservations.store');

        Route::patch('/cliente/reservas/{reservation}/cancelar', [ClientReservationController::class, 'cancel'])
            ->name('client.reservations.cancel');

        Route::get('/cliente/reservas/mesas-disponibles/buscar', [ClientReservationController::class, 'availableTables'])
            ->name('client.reservations.available-tables');
    });
});

Route::middleware(['role:Master|Administrador|Mesero'])->group(function () {
    Route::get('/admin/pedidos', [AdminOrderController::class, 'index'])
        ->name('admin.orders.index');

    Route::get('/admin/pedidos/clientes/buscar', [AdminOrderController::class, 'searchClients'])
        ->name('admin.orders.search-clients');

    Route::post('/admin/pedidos', [AdminOrderController::class, 'store'])
        ->name('admin.orders.store');

    Route::put('/admin/pedidos/{order}', [AdminOrderController::class, 'update'])
        ->name('admin.orders.update');

    Route::patch('/admin/pedidos/{order}/estado', [AdminOrderController::class, 'changeStatus'])
        ->name('admin.orders.change-status');

    Route::patch('/admin/pedidos/{order}/cancelar', [AdminOrderController::class, 'cancel'])
        ->name('admin.orders.cancel');
});

Route::middleware(['role:Cliente'])->group(function () {
    Route::get('/cliente/pedidos', [ClientOrderController::class, 'index'])
        ->name('client.orders.index');

    Route::post('/cliente/pedidos', [ClientOrderController::class, 'store'])
        ->name('client.orders.store');

    Route::patch('/cliente/pedidos/{order}/cancelar', [ClientOrderController::class, 'cancel'])
        ->name('client.orders.cancel');
});

require __DIR__ . '/auth.php';
