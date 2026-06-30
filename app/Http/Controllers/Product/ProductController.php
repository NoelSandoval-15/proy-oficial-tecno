<?php

namespace App\Http\Controllers\Product;

use App\Exports\ProductsExport;
use App\Http\Controllers\Controller;
use App\Models\Categorie;
use App\Models\Product;
use App\Models\Sub_Categorie;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductController extends Controller
{
    private array $allowedPerPage = ['10', '20', '30', '50', '100', 'all'];

    public function index(Request $request): InertiaResponse
    {
        return Inertia::render('Products/Index', [
            ...$this->payload($request),
            'categories' => Categorie::query()
                ->orderBy('name')
                ->get(['id', 'name']),
            'subCategories' => $this->subCategoryOptions(),
        ]);
    }

    public function ajax(Request $request): JsonResponse
    {
        return response()->json([
            'ok' => true,
            ...$this->payload($request),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules(), $this->messages());

        $validated['price'] = max(0, (float) $validated['price']);
        $validated['amount'] = max(0, (int) $validated['amount']);

        if ($request->hasFile('url_photo')) {
            $path = $request->file('url_photo')->store('products', 'public');
            $validated['url_photo'] = '/storage/' . $path;
        }

        Product::create($validated);

        return back()->with('success', 'Producto creado correctamente.');
    }

    public function update(Request $request, Product $item): RedirectResponse
    {
        $validated = $request->validate($this->rules(), $this->messages());

        $validated['price'] = max(0, (float) $validated['price']);
        $validated['amount'] = max(0, (int) $validated['amount']);

        if ($request->hasFile('url_photo')) {
            $this->deletePublicImage($item->url_photo);

            $path = $request->file('url_photo')->store('products', 'public');
            $validated['url_photo'] = '/storage/' . $path;
        } else {
            unset($validated['url_photo']);
        }

        $item->update($validated);

        return back()->with('success', 'Producto actualizado correctamente.');
    }

    public function destroy(Product $item): RedirectResponse
    {
        if ($item->sales_details()->exists()) {
            return back()->with('error', 'No puedes eliminar este producto porque ya tiene ventas registradas.');
        }

        $this->deletePublicImage($item->url_photo);

        $item->delete();

        return back()->with('success', 'Producto eliminado correctamente.');
    }

    public function export(Request $request): BinaryFileResponse
    {
        return $this->exportExcel($request);
    }

    public function exportExcel(Request $request): BinaryFileResponse
    {
        return Excel::download(
            new ProductsExport($this->exportProducts($request)),
            'productos-churrasqueria-roberto.xlsx'
        );
    }

    public function exportTxt(Request $request): StreamedResponse
    {
        $products = $this->exportProducts($request);

        return response()->streamDownload(function () use ($products) {
            echo "PRODUCTOS\n";
            echo "CHURRASQUERÍA ROBERTO\n";
            echo "Generado: " . now('America/La_Paz')->format('d/m/Y H:i:s') . "\n";
            echo str_repeat('=', 110) . "\n\n";

            if ($products->isEmpty()) {
                echo "No hay productos para mostrar.\n";
                return;
            }

            foreach ($products as $index => $product) {
                $number = $index + 1;

                echo "PRODUCTO N° {$number}\n";
                echo str_repeat('-', 110) . "\n";
                echo "Nombre      : " . data_get($product, 'name', '-') . "\n";
                echo "Categoría   : " . data_get($product, 'category_name', '-') . "\n";
                echo "Subcategoría: " . data_get($product, 'sub_category_name', '-') . "\n";
                echo "Precio      : " . number_format((float) data_get($product, 'price', 0), 2) . " Bs\n";
                echo "Cantidad    : " . data_get($product, 'amount', 0) . "\n";
                echo "Estado      : " . data_get($product, 'stock_status_label', '-') . "\n";
                echo "Creado      : " . data_get($product, 'created_at', '-') . "\n";
                echo str_repeat('=', 110) . "\n\n";
            }
        }, 'productos-churrasqueria-roberto.txt', [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }

    public function exportPdf(Request $request): HttpResponse
    {
        $products = $this->exportProducts($request)
            ->take(500)
            ->values();

        $pdf = Pdf::loadView('pdf.products', [
            'title' => 'Reporte de productos',
            'products' => $products,
            'generatedAt' => now('America/La_Paz')->format('d/m/Y H:i:s'),
        ])->setPaper('letter', 'landscape');

        return $pdf->download('productos-churrasqueria-roberto.pdf');
    }

    private function payload(Request $request): array
    {
        $filters = $this->filters($request);
        $query = $this->baseQuery($filters);

        if ($filters['per_page'] === 'all') {
            $collection = $query->get();

            $products = [
                'data' => $collection
                    ->map(fn (Product $product) => $this->mapProduct($product))
                    ->values(),
                'meta' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => 'all',
                    'total' => $collection->count(),
                    'from' => $collection->count() > 0 ? 1 : 0,
                    'to' => $collection->count(),
                ],
            ];
        } else {
            $paginated = $query
                ->paginate((int) $filters['per_page'])
                ->withQueryString();

            $products = [
                'data' => $paginated->getCollection()
                    ->map(fn (Product $product) => $this->mapProduct($product))
                    ->values(),
                'meta' => [
                    'current_page' => $paginated->currentPage(),
                    'last_page' => $paginated->lastPage(),
                    'per_page' => $paginated->perPage(),
                    'total' => $paginated->total(),
                    'from' => $paginated->firstItem(),
                    'to' => $paginated->lastItem(),
                ],
            ];
        }

        return [
            'products' => $products,
            'filters' => $filters,
            'stats' => [
                'products' => Product::count(),
                'stock' => Product::sum('amount'),
                'low_stock' => Product::where('amount', '>', 0)
                    ->where('amount', '<=', 5)
                    ->count(),
                'out_stock' => Product::where('amount', '<=', 0)->count(),
            ],
        ];
    }

    private function baseQuery(array $filters)
    {
        return Product::query()
            ->with('subCategorie.categorie')
            ->when($filters['search'], function ($query, $search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('name', 'ILIKE', "%{$search}%")
                        ->orWhereHas('subCategorie', function ($subCategoryQuery) use ($search) {
                            $subCategoryQuery->where('name', 'ILIKE', "%{$search}%");
                        })
                        ->orWhereHas('subCategorie.categorie', function ($categoryQuery) use ($search) {
                            $categoryQuery->where('name', 'ILIKE', "%{$search}%");
                        });
                });
            })
            ->when($filters['category_id'], function ($query, $categoryId) {
                $query->whereHas('subCategorie', function ($subQuery) use ($categoryId) {
                    $subQuery->where('categories_id', $categoryId);
                });
            })
            ->when($filters['sub_category_id'], function ($query, $subCategoryId) {
                $query->where('sub_categories_id', $subCategoryId);
            })
            ->when($filters['stock_status'], function ($query, $stockStatus) {
                match ($stockStatus) {
                    'available' => $query->where('amount', '>', 5),
                    'low' => $query->where('amount', '>', 0)->where('amount', '<=', 5),
                    'out' => $query->where('amount', '<=', 0),
                    default => null,
                };
            })
            ->orderBy('name');
    }

    private function filters(Request $request): array
    {
        $perPage = (string) $request->input('per_page', '10');

        if (! in_array($perPage, $this->allowedPerPage, true)) {
            $perPage = '10';
        }

        return [
            'search' => trim((string) $request->input('search', '')),
            'category_id' => $request->input('category_id') ?: '',
            'sub_category_id' => $request->input('sub_category_id') ?: '',
            'stock_status' => $request->input('stock_status') ?: '',
            'per_page' => $perPage,
        ];
    }

    private function rules(): array
    {
        return [
            'sub_categories_id' => ['required', 'exists:sub_categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'amount' => ['required', 'integer', 'min:0'],
            'url_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    private function messages(): array
    {
        return [
            'sub_categories_id.required' => 'Debes seleccionar una subcategoría.',
            'sub_categories_id.exists' => 'La subcategoría seleccionada no existe.',
            'name.required' => 'El nombre del producto es obligatorio.',
            'price.required' => 'El precio es obligatorio.',
            'price.numeric' => 'El precio debe ser numérico.',
            'price.min' => 'El precio no puede ser negativo.',
            'amount.required' => 'La cantidad es obligatoria.',
            'amount.integer' => 'La cantidad debe ser un número entero.',
            'amount.min' => 'La cantidad no puede ser negativa.',
            'url_photo.image' => 'El archivo debe ser una imagen.',
        ];
    }

    private function exportProducts(Request $request): Collection
    {
        return $this->baseQuery($this->filters($request))
            ->get()
            ->map(fn (Product $product) => $this->mapProduct($product))
            ->values();
    }

    private function subCategoryOptions(): Collection
    {
        return Sub_Categorie::query()
            ->with('categorie')
            ->orderBy('name')
            ->get()
            ->map(function (Sub_Categorie $subCategory) {
                return [
                    'id' => $subCategory->id,
                    'name' => $subCategory->name,
                    'categories_id' => $subCategory->categories_id,
                    'category_name' => $subCategory->categorie?->name,
                ];
            })
            ->values();
    }

    private function mapProduct(Product $product): array
    {
        $amount = (int) $product->amount;

        $stockStatus = match (true) {
            $amount <= 0 => 'out',
            $amount <= 5 => 'low',
            default => 'available',
        };

        $stockStatusLabel = match ($stockStatus) {
            'out' => 'Sin stock',
            'low' => 'Stock bajo',
            default => 'Disponible',
        };

        return [
            'id' => $product->id,
            'name' => $product->name,
            'price' => (float) $product->price,
            'amount' => $amount,
            'url_photo' => $product->url_photo,
            'sub_categories_id' => $product->sub_categories_id,
            'category_name' => $product->subCategorie?->categorie?->name ?? 'Sin categoría',
            'sub_category_name' => $product->subCategorie?->name ?? 'Sin subcategoría',
            'stock_status' => $stockStatus,
            'stock_status_label' => $stockStatusLabel,
            'created_at' => optional($product->created_at)->format('d/m/Y H:i'),
            'sub_categorie' => $product->subCategorie ? [
                'id' => $product->subCategorie->id,
                'name' => $product->subCategorie->name,
                'categories_id' => $product->subCategorie->categories_id,
                'categorie' => $product->subCategorie->categorie ? [
                    'id' => $product->subCategorie->categorie->id,
                    'name' => $product->subCategorie->categorie->name,
                ] : null,
            ] : null,
        ];
    }

    private function deletePublicImage(?string $url): void
    {
        if (! $url || ! str_starts_with($url, '/storage/')) {
            return;
        }

        $path = str_replace('/storage/', '', $url);

        Storage::disk('public')->delete($path);
    }
}
