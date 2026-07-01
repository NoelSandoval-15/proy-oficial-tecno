<?php

namespace App\Http\Controllers\Insumos;

use App\Exports\Insumos\PurchaseNotesExport;
use App\Http\Controllers\Controller;
use App\Models\Details_Purchases;
use App\Models\Insumo;
use App\Models\Purchase_Notes;
use App\Models\Supplier;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class PurchaseNoteController extends Controller
{
    private array $allowedPerPage = ['10', '20', '30', '50', '100', 'all'];

    private function resolvePerPage(Request $request): string
    {
        $perPage = (string) $request->input('per_page', '10');

        if (! in_array($perPage, $this->allowedPerPage, true)) {
            return '10';
        }

        return $perPage;
    }

    private function purchaseQuery(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $supplierId = $request->input('supplier_id');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $period = $request->input('period', 'all');
        $sort = $request->input('sort', 'recent');

        return Purchase_Notes::query()
            ->with([
                'users:id,name,email',
                'suppliers:id,name,telephone',
                'details_purchases.insumos:id,name,amount,price',
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('date', 'like', "%{$search}%")
                        ->orWhere('hour', 'like', "%{$search}%")
                        ->orWhere('total_price', 'like', "%{$search}%")
                        ->orWhereHas('suppliers', function ($supplierQuery) use ($search) {
                            $supplierQuery
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('telephone', 'like', "%{$search}%");
                        })
                        ->orWhereHas('users', function ($userQuery) use ($search) {
                            $userQuery
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        })
                        ->orWhereHas('details_purchases.insumos', function ($insumoQuery) use ($search) {
                            $insumoQuery->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->when($supplierId, function ($query) use ($supplierId) {
                $query->where('suppliers_id', $supplierId);
            })
            ->when($dateFrom, function ($query) use ($dateFrom) {
                $query->whereDate('date', '>=', $dateFrom);
            })
            ->when($dateTo, function ($query) use ($dateTo) {
                $query->whereDate('date', '<=', $dateTo);
            })
            ->when(! $dateFrom && ! $dateTo && $period !== 'all', function ($query) use ($period) {
                $now = Carbon::now('America/La_Paz');

                $startDate = match ($period) {
                    'this_month' => $now->copy()->startOfMonth()->toDateString(),
                    'last_2_months' => $now->copy()->subMonths(2)->startOfDay()->toDateString(),
                    'last_6_months' => $now->copy()->subMonths(6)->startOfDay()->toDateString(),
                    'last_year' => $now->copy()->subYear()->startOfDay()->toDateString(),
                    'last_2_years' => $now->copy()->subYears(2)->startOfDay()->toDateString(),
                    default => null,
                };

                if ($startDate) {
                    $query->whereDate('date', '>=', $startDate);
                }
            })
            ->when($sort === 'highest_total', function ($query) {
                $query
                    ->orderByDesc('total_price')
                    ->orderByDesc('date')
                    ->orderByDesc('hour')
                    ->orderByDesc('id');
            })
            ->when($sort === 'lowest_total', function ($query) {
                $query
                    ->orderBy('total_price')
                    ->orderByDesc('date')
                    ->orderByDesc('hour')
                    ->orderByDesc('id');
            })
            ->when(! in_array($sort, ['highest_total', 'lowest_total'], true), function ($query) {
                $query
                    ->orderByDesc('date')
                    ->orderByDesc('hour')
                    ->orderByDesc('created_at')
                    ->orderByDesc('id');
            });
    }

    private function buildStats($query): array
    {
        $purchaseIds = (clone $query)->pluck('id');

        return [
            'purchases' => (int) (clone $query)->count(),

            'total_spent' => (float) (clone $query)->sum('total_price'),

            'total_amount' => (int) Details_Purchases::query()
                ->whereIn('purchase_notes_id', $purchaseIds)
                ->sum('amount'),

            'max_purchase' => (float) (clone $query)->max('total_price'),
        ];
    }

    public function index(Request $request)
    {
        $perPage = $this->resolvePerPage($request);
        $query = $this->purchaseQuery($request);

        $stats = $this->buildStats(clone $query);
        $totalRows = (clone $query)->count();

        $purchaseNotes = $query
            ->paginate($perPage === 'all' ? max($totalRows, 1) : (int) $perPage)
            ->withQueryString();

        return Inertia::render('Insumos/Purchases/Index', [
            'purchaseNotes' => $purchaseNotes,

            'suppliers' => Supplier::query()
                ->select('id', 'name', 'telephone')
                ->orderBy('name')
                ->get(),

            'filters' => [
                'search' => trim((string) $request->input('search', '')),
                'supplier_id' => $request->input('supplier_id', ''),
                'date_from' => $request->input('date_from', ''),
                'date_to' => $request->input('date_to', ''),
                'period' => $request->input('period', 'all'),
                'sort' => $request->input('sort', 'recent'),
                'per_page' => $perPage,
            ],

            'stats' => $stats,
            'allowedPerPage' => $this->allowedPerPage,
        ]);
    }

    public function create()
    {
        return Inertia::render('Insumos/Purchases/Create', [
            'suppliers' => Supplier::query()
                ->select('id', 'name', 'telephone')
                ->orderBy('name')
                ->get(),

            'insumos' => Insumo::query()
                ->select('id', 'name', 'amount', 'price')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $adminId = Auth::id();

        if (! $adminId) {
            abort(403, 'No tienes una sesión válida.');
        }

        $validated = $request->validate([
            'suppliers_id' => ['nullable', 'exists:suppliers,id'],

            'details' => ['required', 'array', 'min:1'],
            'details.*.insumos_id' => ['required', 'exists:insumos,id'],
            'details.*.amount' => ['required', 'integer', 'min:1'],
        ], [
            'suppliers_id.exists' => 'El proveedor seleccionado no existe.',

            'details.required' => 'Debe agregar al menos un insumo a la compra.',
            'details.array' => 'El detalle de compra no tiene el formato correcto.',
            'details.min' => 'Debe agregar al menos un insumo a la compra.',

            'details.*.insumos_id.required' => 'Debe seleccionar un insumo.',
            'details.*.insumos_id.exists' => 'Uno de los insumos seleccionados no existe.',

            'details.*.amount.required' => 'Debe ingresar la cantidad comprada.',
            'details.*.amount.integer' => 'La cantidad debe ser un número entero.',
            'details.*.amount.min' => 'La cantidad debe ser mayor a cero.',
        ]);

        DB::transaction(function () use ($validated, $adminId) {
            $now = now('America/La_Paz');

            $purchaseNote = Purchase_Notes::create([
                'hour' => $now->format('H:i:s'),
                'date' => $now->toDateString(),
                'total_price' => 0,
                'users_admin_id' => $adminId,
                'suppliers_id' => $validated['suppliers_id'] ?? null,
            ]);

            $totalPrice = 0;

            foreach ($validated['details'] as $detail) {
                $insumo = Insumo::query()
                    ->where('id', $detail['insumos_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                $amount = (int) $detail['amount'];
                $unitPrice = (float) $insumo->price;
                $subtotal = $amount * $unitPrice;

                Details_Purchases::create([
                    'insumos_id' => $insumo->id,
                    'purchase_notes_id' => $purchaseNote->id,
                    'amount' => $amount,
                    'price_purchase' => $subtotal,
                ]);

                $insumo->increment('amount', $amount);

                $totalPrice += $subtotal;
            }

            $purchaseNote->update([
                'total_price' => $totalPrice,
            ]);
        });

        return redirect()
            ->route('insumos.purchases.index')
            ->with('success', 'Compra de insumos registrada correctamente.');
    }

    public function show(Purchase_Notes $purchaseNote)
    {
        $purchaseNote->load([
            'users:id,name,email',
            'suppliers:id,name,telephone,description,url_photo',
            'details_purchases.insumos:id,name,amount,price',
        ]);

        return Inertia::render('Insumos/Purchases/Show', [
            'purchaseNote' => $purchaseNote,
        ]);
    }

    public function destroy(Purchase_Notes $purchaseNote)
    {
        DB::transaction(function () use ($purchaseNote) {
            $purchaseNote->load('details_purchases');

            foreach ($purchaseNote->details_purchases as $detail) {
                $insumo = Insumo::query()
                    ->where('id', $detail->insumos_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($insumo->amount < $detail->amount) {
                    throw ValidationException::withMessages([
                        'purchase' => 'No se puede eliminar esta compra porque algunos insumos ya fueron utilizados.',
                    ]);
                }

                $insumo->decrement('amount', $detail->amount);
            }

            $purchaseNote->details_purchases()->delete();
            $purchaseNote->delete();
        });

        return back()->with('success', 'Compra eliminada y stock revertido correctamente.');
    }

    public function exportExcel(Request $request)
    {
        $purchaseNotes = $this->purchaseQuery($request)->get();

        return Excel::download(
            new PurchaseNotesExport($purchaseNotes),
            'compras_insumos.xlsx'
        );
    }

    public function exportPdf(Request $request)
    {
        $purchaseNotes = $this->purchaseQuery($request)->get();

        $pdf = Pdf::loadView('exports.insumos.purchase-notes-pdf', [
            'title' => 'Reporte de compras de insumos',
            'purchaseNotes' => $purchaseNotes,
        ])->setPaper('letter', 'landscape');

        return $pdf->download('compras_insumos.pdf');
    }

    public function exportTxt(Request $request)
    {
        $purchaseNotes = $this->purchaseQuery($request)->get();

        $content = "COMPRAS DE INSUMOS\n";
        $content .= "Churrasquería Roberto\n";
        $content .= "Generado: " . now('America/La_Paz')->format('d/m/Y H:i:s') . "\n\n";

        foreach ($purchaseNotes as $purchaseNote) {
            $content .= "Fecha: {$purchaseNote->date}\n";
            $content .= "Hora: {$purchaseNote->hour}\n";
            $content .= "Proveedor: " . ($purchaseNote->suppliers?->name ?? 'Sin proveedor') . "\n";
            $content .= "Administrador: " . ($purchaseNote->users?->name ?? 'Sin usuario') . "\n";
            $content .= "Total: Bs. {$purchaseNote->total_price}\n";
            $content .= "Detalle:\n";

            foreach ($purchaseNote->details_purchases as $detail) {
                $insumoName = $detail->insumos?->name ?? 'Insumo eliminado';

                $content .= "- {$insumoName} | Cantidad: {$detail->amount} | Subtotal: Bs. {$detail->price_purchase}\n";
            }

            $content .= "----------------------------------------\n";
        }

        return response($content, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="compras_insumos.txt"',
        ]);
    }
}
