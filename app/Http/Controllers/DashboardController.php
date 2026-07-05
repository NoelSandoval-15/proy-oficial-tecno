<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Sales_Detail;
use App\Models\Sales_Note;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $user = auth()->user();
        $roles = $this->roles($user);
        $roleGroup = $this->roleGroup($roles);

        $dashboard = match ($roleGroup) {
            'admin' => $this->adminDashboard($roles),
            'waiter' => $this->waiterDashboard($user, $roles),
            'client' => $this->clientDashboard($user, $roles),
            default => $this->clientDashboard($user, $roles),
        };

        return Inertia::render('Dashboard', [
            'dashboard' => $dashboard,
        ]);
    }

    private function adminDashboard(array $roles): array
    {
        $today = now()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();

        $paidToday = Payment::query()
            ->where('status', Payment::STATUS_PAID)
            ->whereDate('paid_at', $today);

        $paidMonth = Payment::query()
            ->where('status', Payment::STATUS_PAID)
            ->whereDate('paid_at', '>=', $monthStart);

        $pendingSales = Sales_Note::query()
            ->where('status', Sales_Note::STATUS_DELIVERED);

        $qrActive = Payment::query()
            ->where('status', Payment::STATUS_QR_GENERATED)
            ->whereNotNull('qr_base64')
            ->where(function ($query) {
                $query->whereNull('expiration_date')
                    ->orWhere('expiration_date', '>=', now());
            });

        return [
            'role_group' => 'admin',
            'roles' => $roles,
            'hero' => [
                'eyebrow' => 'Panel gerencial',
                'title' => 'Control total de caja, ventas y cobros',
                'subtitle' => 'Visualiza ingresos, ventas entregadas, pagos QR, efectivo y rendimiento general de la churrasquería.',
                'badge' => in_array('Master', $roles, true) ? 'Master' : 'Administrador',
            ],
            'metrics' => [
                [
                    'label' => 'Ingresos de hoy',
                    'value' => (float) $paidToday->clone()->sum('amount_received'),
                    'type' => 'money',
                    'helper' => $paidToday->clone()->count() . ' pagos confirmados hoy',
                    'tone' => 'emerald',
                ],
                [
                    'label' => 'Por cobrar',
                    'value' => (float) $pendingSales->clone()->sum('total_price'),
                    'type' => 'money',
                    'helper' => $pendingSales->clone()->count() . ' ventas entregadas',
                    'tone' => 'amber',
                ],
                [
                    'label' => 'QR activos',
                    'value' => (int) $qrActive->clone()->count(),
                    'type' => 'number',
                    'helper' => 'Verificación automática disponible',
                    'tone' => 'blue',
                ],
                [
                    'label' => 'Ingresos del mes',
                    'value' => (float) $paidMonth->sum('amount_received'),
                    'type' => 'money',
                    'helper' => 'Desde ' . now()->startOfMonth()->format('d/m/Y'),
                    'tone' => 'primary',
                ],
            ],
            'status_breakdown' => $this->statusBreakdown(),
            'top_products' => $this->topProducts(),
            'recent_sales' => $this->recentSales(),
            'quick_actions' => [
                [
                    'label' => 'Gestionar pagos',
                    'description' => 'Cobrar QR, efectivo y revisar notas.',
                    'url' => Route::has('payments.index') ? route('payments.index') : '#',
                    'tone' => 'primary',
                ],
                [
                    'label' => 'Reservas internas',
                    'description' => 'Controlar reservas y mesas.',
                    'url' => Route::has('admin.reservations.index') ? route('admin.reservations.index') : '#',
                    'tone' => 'blue',
                ],
                [
                    'label' => 'Insumos',
                    'description' => 'Revisar compras, proveedores e inventario.',
                    'url' => Route::has('insumos.purchases.index') ? route('insumos.purchases.index') : '#',
                    'tone' => 'amber',
                ],
            ],
        ];
    }

    private function waiterDashboard($user, array $roles): array
    {
        $today = now()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();

        $mySales = Sales_Note::query()
            ->where('users_admin_id', $user->id);

        $myPaidToday = Payment::query()
            ->where('status', Payment::STATUS_PAID)
            ->whereDate('paid_at', $today)
            ->whereHas('salesNote', function ($query) use ($user) {
                $query->where('users_admin_id', $user->id);
            });

        $myPaidMonth = Payment::query()
            ->where('status', Payment::STATUS_PAID)
            ->whereDate('paid_at', '>=', $monthStart)
            ->whereHas('salesNote', function ($query) use ($user) {
                $query->where('users_admin_id', $user->id);
            });

        $myPending = $mySales->clone()
            ->where('status', Sales_Note::STATUS_DELIVERED);

        $myQrActive = Payment::query()
            ->where('status', Payment::STATUS_QR_GENERATED)
            ->whereNotNull('qr_base64')
            ->whereHas('salesNote', function ($query) use ($user) {
                $query->where('users_admin_id', $user->id);
            })
            ->where(function ($query) {
                $query->whereNull('expiration_date')
                    ->orWhere('expiration_date', '>=', now());
            });

        return [
            'role_group' => 'waiter',
            'roles' => $roles,
            'hero' => [
                'eyebrow' => 'Panel del mesero',
                'title' => 'Tus ventas, cobros y pedidos atendidos',
                'subtitle' => 'Controla tus ventas entregadas, pagos confirmados, QR activos y notas generadas por tus atenciones.',
                'badge' => 'Mesero',
            ],
            'metrics' => [
                [
                    'label' => 'Cobrado hoy',
                    'value' => (float) $myPaidToday->clone()->sum('amount_received'),
                    'type' => 'money',
                    'helper' => $myPaidToday->clone()->count() . ' pagos confirmados',
                    'tone' => 'emerald',
                ],
                [
                    'label' => 'Pendiente por cobrar',
                    'value' => (float) $myPending->clone()->sum('total_price'),
                    'type' => 'money',
                    'helper' => $myPending->clone()->count() . ' ventas entregadas',
                    'tone' => 'amber',
                ],
                [
                    'label' => 'QR activos',
                    'value' => (int) $myQrActive->count(),
                    'type' => 'number',
                    'helper' => 'Esperando confirmación',
                    'tone' => 'blue',
                ],
                [
                    'label' => 'Cobrado este mes',
                    'value' => (float) $myPaidMonth->sum('amount_received'),
                    'type' => 'money',
                    'helper' => 'Rendimiento mensual',
                    'tone' => 'primary',
                ],
            ],
            'status_breakdown' => $this->statusBreakdown($user->id),
            'top_products' => $this->topProducts($user->id),
            'recent_sales' => $this->recentSales($user->id),
            'quick_actions' => [
                [
                    'label' => 'Ir a pagos',
                    'description' => 'Cobrar ventas entregadas.',
                    'url' => Route::has('payments.index') ? route('payments.index') : '#',
                    'tone' => 'primary',
                ],
                [
                    'label' => 'Reservas internas',
                    'description' => 'Ver mesas y reservas asignadas.',
                    'url' => Route::has('admin.reservations.index') ? route('admin.reservations.index') : '#',
                    'tone' => 'blue',
                ],
            ],
        ];
    }

    private function clientDashboard($user, array $roles): array
    {
        $clientSales = Sales_Note::query()
            ->where('users_client_id', $user->id);

        $clientPending = $clientSales->clone()
            ->where('status', Sales_Note::STATUS_DELIVERED);

        $clientPaidPayments = Payment::query()
            ->where('status', Payment::STATUS_PAID)
            ->whereHas('salesNote', function ($query) use ($user) {
                $query->where('users_client_id', $user->id);
            });

        $clientQrActive = Payment::query()
            ->where('status', Payment::STATUS_QR_GENERATED)
            ->whereNotNull('qr_base64')
            ->whereHas('salesNote', function ($query) use ($user) {
                $query->where('users_client_id', $user->id);
            })
            ->where(function ($query) {
                $query->whereNull('expiration_date')
                    ->orWhere('expiration_date', '>=', now());
            });

        return [
            'role_group' => 'client',
            'roles' => $roles,
            'hero' => [
                'eyebrow' => 'Panel del cliente',
                'title' => 'Tus consumos, deudas y pagos',
                'subtitle' => 'Consulta tus pedidos entregados, pagos confirmados, QR activos y notas de venta disponibles.',
                'badge' => 'Cliente',
            ],
            'metrics' => [
                [
                    'label' => 'Deuda actual',
                    'value' => (float) $clientPending->clone()->sum('total_price'),
                    'type' => 'money',
                    'helper' => $clientPending->clone()->count() . ' pedido(s) por pagar',
                    'tone' => 'amber',
                ],
                [
                    'label' => 'Total consumido',
                    'value' => (float) $clientPaidPayments->clone()->sum('amount_received'),
                    'type' => 'money',
                    'helper' => $clientPaidPayments->clone()->count() . ' pagos confirmados',
                    'tone' => 'emerald',
                ],
                [
                    'label' => 'QR activos',
                    'value' => (int) $clientQrActive->count(),
                    'type' => 'number',
                    'helper' => 'Pagos esperando confirmación',
                    'tone' => 'blue',
                ],
                [
                    'label' => 'Pedidos registrados',
                    'value' => (int) $clientSales->count(),
                    'type' => 'number',
                    'helper' => 'Historial de consumos',
                    'tone' => 'primary',
                ],
            ],
            'status_breakdown' => $this->statusBreakdown(null, $user->id),
            'top_products' => $this->topProducts(null, $user->id),
            'recent_sales' => $this->recentSales(null, $user->id),
            'quick_actions' => [
                [
                    'label' => 'Mis pagos',
                    'description' => 'Ver deudas, QR y notas de venta.',
                    'url' => Route::has('client.payments.index') ? route('client.payments.index') : '#',
                    'tone' => 'primary',
                ],
                [
                    'label' => 'Mis reservas',
                    'description' => 'Revisar reservas activas.',
                    'url' => Route::has('client.reservations.index') ? route('client.reservations.index') : '#',
                    'tone' => 'blue',
                ],
            ],
        ];
    }

    private function statusBreakdown(?int $adminId = null, ?int $clientId = null): array
    {
        $query = Sales_Note::query();

        if ($adminId) {
            $query->where('users_admin_id', $adminId);
        }

        if ($clientId) {
            $query->where('users_client_id', $clientId);
        }

        $counts = $query
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        return collect(Sales_Note::statuses())
            ->map(function ($status) use ($counts) {
                return [
                    'label' => $status,
                    'count' => (int) ($counts[$status] ?? 0),
                ];
            })
            ->values()
            ->all();
    }

    private function topProducts(?int $adminId = null, ?int $clientId = null): array
    {
        $query = Sales_Detail::query()
            ->select([
                'products_id',
                DB::raw('SUM(amount) as quantity'),
                DB::raw('SUM(amount * price_sale) as total'),
            ])
            ->with('product')
            ->whereHas('sales_note', function ($saleQuery) use ($adminId, $clientId) {
                if ($adminId) {
                    $saleQuery->where('users_admin_id', $adminId);
                }

                if ($clientId) {
                    $saleQuery->where('users_client_id', $clientId);
                }
            })
            ->groupBy('products_id')
            ->orderByDesc(DB::raw('SUM(amount)'))
            ->limit(5)
            ->get();

        return $query->map(function ($item) {
            return [
                'name' => $item->product?->name ?? 'Producto',
                'quantity' => (int) $item->quantity,
                'total' => (float) $item->total,
            ];
        })->values()->all();
    }

    private function recentSales(?int $adminId = null, ?int $clientId = null): array
    {
        $query = Sales_Note::query()
            ->with([
                'users_client.profile',
                'latestPayment',
                'paidPayment',
                'activePayment',
                'details.product',
            ]);

        if ($adminId) {
            $query->where('users_admin_id', $adminId);
        }

        if ($clientId) {
            $query->where('users_client_id', $clientId);
        }

        return $query
            ->latest('id')
            ->limit(6)
            ->get()
            ->map(function ($sale) {
                $payment = $sale->paidPayment ?? $sale->activePayment ?? $sale->latestPayment;
                $profile = $sale->users_client?->profile;

                $clientName = trim(($profile?->name ?? '') . ' ' . ($profile?->last_name ?? ''));

                return [
                    'id' => $sale->id,
                    'client' => $clientName ?: ($sale->users_client?->name ?? 'Cliente mostrador'),
                    'status' => $sale->status,
                    'date' => optional($sale->date)->format('Y-m-d') ?: $sale->date,
                    'hour' => $sale->hour,
                    'total' => (float) $sale->total_price,
                    'payment_status' => $payment?->status,
                    'payment_method' => $payment?->payment_method,
                    'items_count' => $sale->details->sum('amount'),
                ];
            })
            ->values()
            ->all();
    }

    private function roles($user): array
    {
        if (!$user) {
            return [];
        }

        if (method_exists($user, 'getRoleNames')) {
            return $user->getRoleNames()->values()->all();
        }

        if (method_exists($user, 'roles')) {
            return $user->roles()->pluck('name')->values()->all();
        }

        return [];
    }

    private function roleGroup(array $roles): string
    {
        if (array_intersect($roles, ['Master', 'Administrador'])) {
            return 'admin';
        }

        if (in_array('Mesero', $roles, true)) {
            return 'waiter';
        }

        return 'client';
    }
}
