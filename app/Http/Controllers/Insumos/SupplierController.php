<?php

namespace App\Http\Controllers\Insumos;

use App\Exports\Insumos\SuppliersExport;
use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class SupplierController extends Controller
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

    private function supplierQuery(Request $request)
    {
        $search = trim((string) $request->input('search', ''));

        return Supplier::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('telephone', 'like', "%{$search}%");
                });
            })
            ->orderBy('name');
    }

    public function index(Request $request)
    {
        $perPage = $this->resolvePerPage($request);
        $query = $this->supplierQuery($request);

        $totalRows = (clone $query)->count();

        $suppliers = $query
            ->paginate($perPage === 'all' ? max($totalRows, 1) : (int) $perPage)
            ->withQueryString();

        return Inertia::render('Insumos/Suppliers/Index', [
            'suppliers' => $suppliers,
            'filters' => [
                'search' => trim((string) $request->input('search', '')),
                'per_page' => $perPage,
            ],
            'stats' => [
                'suppliers' => $totalRows,
            ],
            'allowedPerPage' => $this->allowedPerPage,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => ['required', 'string', 'max:500'],
            'telephone' => ['required', 'integer'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg'],
        ], [
            'name.required' => 'El nombre del proveedor es obligatorio.',
            'name.max' => 'El nombre no debe superar los 150 caracteres.',
            'description.required' => 'La descripción del proveedor es obligatoria.',
            'description.max' => 'La descripción no debe superar los 500 caracteres.',
            'telephone.required' => 'El teléfono del proveedor es obligatorio.',
            'telephone.integer' => 'El teléfono debe ser numérico.',
            'photo.image' => 'La foto debe ser una imagen.',
            'photo.mimes' => 'La foto debe estar en formato JPG o JPEG.',
        ]);

        $photoUrl = null;

        if ($request->hasFile('photo')) {
            $photoUrl = $this->storeSupplierPhoto(
                request: $request,
                name: $validated['name']
            );
        }

        Supplier::create([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'telephone' => $validated['telephone'],
            'url_photo' => $photoUrl,
        ]);

        return back()->with('success', 'Proveedor registrado correctamente.');
    }

    public function update(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => ['required', 'string', 'max:500'],
            'telephone' => ['required', 'integer'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg'],
        ], [
            'name.required' => 'El nombre del proveedor es obligatorio.',
            'name.max' => 'El nombre no debe superar los 150 caracteres.',
            'description.required' => 'La descripción del proveedor es obligatoria.',
            'description.max' => 'La descripción no debe superar los 500 caracteres.',
            'telephone.required' => 'El teléfono del proveedor es obligatorio.',
            'telephone.integer' => 'El teléfono debe ser numérico.',
            'photo.image' => 'La foto debe ser una imagen.',
            'photo.mimes' => 'La foto debe estar en formato JPG o JPEG.',
        ]);

        $photoUrl = $supplier->url_photo;

        if ($request->hasFile('photo')) {
            $this->deleteOldPublicPhoto($supplier->url_photo);

            $photoUrl = $this->storeSupplierPhoto(
                request: $request,
                name: $validated['name']
            );
        }

        $supplier->update([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'telephone' => $validated['telephone'],
            'url_photo' => $photoUrl,
        ]);

        return back()->with('success', 'Proveedor actualizado correctamente.');
    }

    public function destroy(Supplier $supplier)
    {
        if ($supplier->purchase_notes()->exists()) {
            return back()->with('error', 'No se puede eliminar este proveedor porque tiene compras registradas.');
        }

        $this->deleteOldPublicPhoto($supplier->url_photo);

        $supplier->delete();

        return back()->with('success', 'Proveedor eliminado correctamente.');
    }

    public function exportExcel(Request $request)
    {
        $suppliers = $this->supplierQuery($request)->get();

        return Excel::download(
            new SuppliersExport($suppliers),
            'proveedores_insumos.xlsx'
        );
    }

    public function exportPdf(Request $request)
    {
        $suppliers = $this->supplierQuery($request)->get();

        $pdf = Pdf::loadView('exports.insumos.suppliers-pdf', [
            'title' => 'Reporte de proveedores de insumos',
            'suppliers' => $suppliers,
        ])->setPaper('letter', 'landscape');

        return $pdf->download('proveedores_insumos.pdf');
    }

    public function exportTxt(Request $request)
    {
        $suppliers = $this->supplierQuery($request)->get();

        $content = "PROVEEDORES DE INSUMOS\n";
        $content .= "Churrasquería Roberto\n";
        $content .= "Generado: " . now('America/La_Paz')->format('d/m/Y H:i:s') . "\n\n";

        foreach ($suppliers as $supplier) {
            $content .= "Proveedor: {$supplier->name}\n";
            $content .= "Descripción: {$supplier->description}\n";
            $content .= "Teléfono: {$supplier->telephone}\n";
            // $content .= "Imagen: " . ($supplier->url_photo ?: 'Sin imagen') . "\n";
            $content .= "Registrado: " . optional($supplier->created_at)->format('d/m/Y H:i') . "\n";
            $content .= "----------------------------------------\n";
        }

        return response($content, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="proveedores_insumos.txt"',
        ]);
    }

    private function storeSupplierPhoto(Request $request, string $name): string
    {
        $folder = 'suppliers';

        $fileNameBase = Str::slug($name, '');
        $fileName = ($fileNameBase ?: 'proveedor') . '.jpg';

        $path = $request->file('photo')->storeAs($folder, $fileName, 'public');

        return Storage::url($path);
    }

    private function deleteOldPublicPhoto(?string $url): void
    {
        if (! $url || ! str_starts_with($url, '/storage/')) {
            return;
        }

        $path = str_replace('/storage/', '', $url);

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
