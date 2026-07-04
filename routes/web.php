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
use App\Http\Controllers\Insumos\SupplierController;
use App\Http\Controllers\Insumos\PurchaseNoteController;
use App\Http\Controllers\Insumos\InsumoNoteController;
use App\Http\Controllers\Tickets\ClientTicketController;
use App\Http\Controllers\Tickets\TicketBoardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Payments\PaymentController;
use App\Http\Controllers\Payments\ClientPaymentController;

// Route::get('/', function () {
//     return Inertia::render('Welcome', [
//         'canLogin' => Route::has('login'),
//         'canRegister' => Route::has('register'),
//         'laravelVersion' => Application::VERSION,
//         'phpVersion' => PHP_VERSION,
//     ]);
// });
Route::get('/', HomeController::class)->name('home');

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
    'role:Master|Administrador',
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

Route::middleware(['auth', 'role:Master|Administrador'])
    ->prefix('insumos')
    ->name('insumos.')
    ->group(function () {
        Route::get('/proveedores', [SupplierController::class, 'index'])
            ->name('suppliers.index');

        Route::post('/proveedores', [SupplierController::class, 'store'])
            ->name('suppliers.store');

        Route::put('/proveedores/{supplier}', [SupplierController::class, 'update'])
            ->name('suppliers.update');

        Route::delete('/proveedores/{supplier}', [SupplierController::class, 'destroy'])
            ->name('suppliers.destroy');

        Route::get('/proveedores/exportar/excel', [SupplierController::class, 'exportExcel'])
            ->name('suppliers.export.excel');

        Route::get('/proveedores/exportar/pdf', [SupplierController::class, 'exportPdf'])
            ->name('suppliers.export.pdf');

        Route::get('/proveedores/exportar/txt', [SupplierController::class, 'exportTxt'])
            ->name('suppliers.export.txt');

        Route::get('/compras', [PurchaseNoteController::class, 'index'])
            ->name('purchases.index');

        Route::get('/compras/crear', [PurchaseNoteController::class, 'create'])
            ->name('purchases.create');

        Route::post('/compras', [PurchaseNoteController::class, 'store'])
            ->name('purchases.store');

        Route::get('/compras/exportar/excel', [PurchaseNoteController::class, 'exportExcel'])
            ->name('purchases.export.excel');

        Route::get('/compras/exportar/pdf', [PurchaseNoteController::class, 'exportPdf'])
            ->name('purchases.export.pdf');

        Route::get('/compras/exportar/txt', [PurchaseNoteController::class, 'exportTxt'])
            ->name('purchases.export.txt');

        Route::get('/compras/{purchaseNote}', [PurchaseNoteController::class, 'show'])
            ->name('purchases.show');

        Route::delete('/compras/{purchaseNote}', [PurchaseNoteController::class, 'destroy'])
            ->name('purchases.destroy');

        Route::get('/notas', [InsumoNoteController::class, 'index'])
            ->name('notes.index');

        Route::post('/notas', [InsumoNoteController::class, 'store'])
            ->name('notes.store');

        Route::delete('/notas/{insumosNote}', [InsumoNoteController::class, 'destroy'])
            ->name('notes.destroy');

        Route::get('/notas/exportar/excel', [InsumoNoteController::class, 'exportExcel'])
            ->name('notes.export.excel');

        Route::get('/notas/exportar/pdf', [InsumoNoteController::class, 'exportPdf'])
            ->name('notes.export.pdf');

        Route::get('/notas/exportar/txt', [InsumoNoteController::class, 'exportTxt'])
            ->name('notes.export.txt');
    });

Route::middleware(['auth', 'role:Cliente'])
    ->prefix('cliente/tickets')
    ->name('client.tickets.')
    ->group(function () {
        Route::get('/', [ClientTicketController::class, 'index'])->name('index');
        Route::get('/{ticket}', [ClientTicketController::class, 'show'])->name('show');
    });

Route::middleware(['auth', 'role:Master|Administrador|Mesero'])
    ->prefix('tickets')
    ->name('tickets.')
    ->group(function () {
        Route::get('/', [TicketBoardController::class, 'index'])->name('index');
        Route::get('/{ticket}', [TicketBoardController::class, 'show'])->name('show');
        Route::patch('/{ticket}/estado', [TicketBoardController::class, 'changeStatus'])->name('change-status');
    });

Route::middleware(['auth', 'verified', 'role:Master|Administrador|Mesero'])
    ->prefix('pagos')
    ->name('payments.')
    ->group(function () {
        Route::get('/', [PaymentController::class, 'index'])
            ->name('index');

        Route::post('/ventas/{salesNote}/generar-qr', [PaymentController::class, 'generateQr'])
            ->name('generate-qr');

        Route::post('/{payment}/consultar', [PaymentController::class, 'checkStatus'])
            ->name('check-status');

        Route::post('/ventas/{salesNote}/manual', [PaymentController::class, 'registerManualPayment'])
            ->name('manual');
    });

Route::middleware(['auth', 'verified', 'role:Cliente'])
    ->prefix('cliente/pagos')
    ->name('client.payments.')
    ->group(function () {
        Route::get('/', [ClientPaymentController::class, 'index'])
            ->name('index');

        Route::post('/ventas/{salesNote}/generar-qr', [ClientPaymentController::class, 'generateQr'])
            ->name('generate-qr');

        Route::post('/{payment}/consultar', [ClientPaymentController::class, 'checkStatus'])
            ->name('check-status');
    });
require __DIR__ . '/auth.php';
