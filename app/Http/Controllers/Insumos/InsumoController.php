<?php

namespace App\Http\Controllers\Insumos;

use App\Http\Controllers\Controller;
use App\Models\Insumo;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InsumoController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $stock = $request->input('stock');

        $insumos = Insumo::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where('name', 'ilike', "%{$search}%");
            })
            ->when($stock === 'low', function ($query) {
                $query->where('amount', '>', 0)
                    ->where('amount', '<=', 5);
            })
            ->when($stock === 'empty', function ($query) {
                $query->where('amount', '<=', 0);
            })
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Insumos/Insumos/Index', [
            'insumos' => $insumos,
            'filters' => [
                'search' => $search,
                'stock' => $stock,
            ],
            'summary' => [
                'total' => Insumo::count(),
                'available' => Insumo::where('amount', '>', 5)->count(),
                'low_stock' => Insumo::where('amount', '>', 0)->where('amount', '<=', 5)->count(),
                'empty_stock' => Insumo::where('amount', '<=', 0)->count(),
                'inventory_value' => (float) Insumo::query()
                    ->selectRaw('COALESCE(SUM(amount * price), 0) as total')
                    ->value('total'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150', 'unique:insumos,name'],
            'amount' => ['required', 'numeric', 'min:0'],
            'price' => ['required', 'numeric', 'min:0'],
        ], [
            'name.required' => 'El nombre del insumo es obligatorio.',
            'name.unique' => 'Ya existe un insumo con ese nombre.',
            'amount.required' => 'La cantidad inicial es obligatoria.',
            'amount.numeric' => 'La cantidad debe ser numérica.',
            'price.required' => 'El precio es obligatorio.',
            'price.numeric' => 'El precio debe ser numérico.',
        ]);

        Insumo::create($data);

        return back()->with('success', 'Insumo registrado correctamente.');
    }

    public function update(Request $request, Insumo $insumo)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150', 'unique:insumos,name,' . $insumo->id],
            'amount' => ['required', 'numeric', 'min:0'],
            'price' => ['required', 'numeric', 'min:0'],
        ], [
            'name.required' => 'El nombre del insumo es obligatorio.',
            'name.unique' => 'Ya existe un insumo con ese nombre.',
            'amount.required' => 'La cantidad es obligatoria.',
            'amount.numeric' => 'La cantidad debe ser numérica.',
            'price.required' => 'El precio es obligatorio.',
            'price.numeric' => 'El precio debe ser numérico.',
        ]);

        $insumo->update($data);

        return back()->with('success', 'Insumo actualizado correctamente.');
    }

    public function destroy(Insumo $insumo)
    {
        $insumo->loadCount([
            'details_insumos',
            'details_purchases',
        ]);

        if ($insumo->details_insumos_count > 0 || $insumo->details_purchases_count > 0) {
            return back()->with('error', 'No puedes eliminar este insumo porque ya tiene movimientos o compras registradas.');
        }

        $insumo->delete();

        return back()->with('success', 'Insumo eliminado correctamente.');
    }
}
