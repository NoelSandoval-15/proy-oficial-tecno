<?php

namespace App\Http\Controllers\Product;

use App\Exports\ProductCategoriesExport;
use App\Http\Controllers\Controller;
use App\Models\Categorie;
use App\Models\Sub_Categorie;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CategorieController extends Controller
{
    private array $allowedPerPage = ['10', '20', '30', '50', '100', 'all'];

    public function index(Request $request): InertiaResponse
    {
        return Inertia::render('Products/Categories/Index', [
            ...$this->payload($request),
            'categoryOptions' => Categorie::query()
                ->orderBy('name')
                ->get(['id', 'name']),
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
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories,name'],
        ], [
            'name.required' => 'El nombre de la categoría es obligatorio.',
            'name.unique' => 'Ya existe una categoría con ese nombre.',
        ]);

        Categorie::create($validated);

        return back()->with('success', 'Categoría creada correctamente.');
    }

    public function update(Request $request, Categorie $category): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories,name,' . $category->id],
        ], [
            'name.required' => 'El nombre de la categoría es obligatorio.',
            'name.unique' => 'Ya existe una categoría con ese nombre.',
        ]);

        $category->update($validated);

        return back()->with('success', 'Categoría actualizada correctamente.');
    }

    public function destroy(Categorie $category): RedirectResponse
    {
        $category->load('subCategories.products');

        $hasProducts = $category->subCategories->some(function ($subCategory) {
            return $subCategory->products->count() > 0;
        });

        if ($hasProducts) {
            return back()->with('error', 'No puedes eliminar esta categoría porque tiene productos registrados.');
        }

        $category->delete();

        return back()->with('success', 'Categoría eliminada correctamente.');
    }

    public function export(Request $request): BinaryFileResponse
    {
        return $this->exportExcel($request);
    }

    public function exportExcel(Request $request): BinaryFileResponse
    {
        return Excel::download(
            new ProductCategoriesExport($this->exportCategories($request)),
            'categorias-productos-churrasqueria-roberto.xlsx'
        );
    }

    public function exportTxt(Request $request): StreamedResponse
    {
        $categories = $this->exportCategories($request);

        return response()->streamDownload(function () use ($categories) {
            echo "CATEGORÍAS Y SUBCATEGORÍAS\n";
            echo "CHURRASQUERÍA ROBERTO\n";
            echo "Generado: " . now('America/La_Paz')->format('d/m/Y H:i:s') . "\n";
            echo str_repeat('=', 100) . "\n\n";

            if ($categories->isEmpty()) {
                echo "No hay categorías para mostrar.\n";
                return;
            }

            foreach ($categories as $index => $category) {
                $number = $index + 1;
                $name = data_get($category, 'name', '-');
                $subCategories = collect(data_get($category, 'sub_categories', []));
                $productsCount = $subCategories->sum('products_count');

                echo "CATEGORÍA N° {$number}\n";
                echo str_repeat('-', 100) . "\n";
                echo "Nombre             : {$name}\n";
                echo "Subcategorías      : " . $subCategories->count() . "\n";
                echo "Productos asociados: {$productsCount}\n";

                if ($subCategories->isEmpty()) {
                    echo "Detalle            : Sin subcategorías\n";
                } else {
                    echo "Detalle:\n";

                    foreach ($subCategories as $subCategory) {
                        echo "  - " . data_get($subCategory, 'name', '-') .
                            " | Productos: " . data_get($subCategory, 'products_count', 0) . "\n";
                    }
                }

                echo str_repeat('=', 100) . "\n\n";
            }
        }, 'categorias-productos-churrasqueria-roberto.txt', [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }

    public function exportPdf(Request $request): HttpResponse
    {
        $categories = $this->exportCategories($request)
            ->take(500)
            ->values();

        $pdf = Pdf::loadView('pdf.product-categories', [
            'title' => 'Reporte de categorías y subcategorías',
            'categories' => $categories,
            'generatedAt' => now('America/La_Paz')->format('d/m/Y H:i:s'),
        ])->setPaper('letter', 'landscape');

        return $pdf->download('categorias-productos-churrasqueria-roberto.pdf');
    }

    private function payload(Request $request): array
    {
        $filters = $this->filters($request);
        $query = $this->baseQuery($filters);

        if ($filters['per_page'] === 'all') {
            $collection = $query->get();

            $categories = [
                'data' => $collection
                    ->map(fn (Categorie $category) => $this->mapCategory($category))
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

            $categories = [
                'data' => $paginated->getCollection()
                    ->map(fn (Categorie $category) => $this->mapCategory($category))
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
            'categories' => $categories,
            'filters' => $filters,
            'stats' => [
                'categories' => Categorie::count(),
                'sub_categories' => Sub_Categorie::count(),
                'with_products' => Sub_Categorie::has('products')->count(),
            ],
        ];
    }

    private function baseQuery(array $filters)
    {
        return Categorie::query()
            ->with([
                'subCategories' => function ($query) {
                    $query
                        ->withCount('products')
                        ->orderBy('name');
                },
            ])
            ->withCount('subCategories')
            ->when($filters['search'], function ($query, $search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('name', 'ILIKE', "%{$search}%")
                        ->orWhereHas('subCategories', function ($subCategoryQuery) use ($search) {
                            $subCategoryQuery->where('name', 'ILIKE', "%{$search}%");
                        });
                });
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
            'per_page' => $perPage,
        ];
    }

    private function exportCategories(Request $request): Collection
    {
        return $this->baseQuery($this->filters($request))
            ->get()
            ->map(fn (Categorie $category) => $this->mapCategory($category))
            ->values();
    }

    private function mapCategory(Categorie $category): array
    {
        return [
            'id' => $category->id,
            'name' => $category->name,
            'sub_categories_count' => (int) ($category->sub_categories_count ?? $category->subCategories->count()),
            'created_at' => optional($category->created_at)->format('d/m/Y H:i'),
            'sub_categories' => $category->subCategories
                ->map(function (Sub_Categorie $subCategory) {
                    return [
                        'id' => $subCategory->id,
                        'name' => $subCategory->name,
                        'url_photo' => $subCategory->url_photo,
                        'categories_id' => $subCategory->categories_id,
                        'products_count' => (int) ($subCategory->products_count ?? 0),
                    ];
                })
                ->values(),
        ];
    }
}
