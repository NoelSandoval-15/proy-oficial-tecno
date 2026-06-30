<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Models\Sub_Categorie;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SubCategorieController extends Controller
{
    public function ajax(Request $request): JsonResponse
    {
        $categoryId = $request->input('category_id');

        $subCategories = Sub_Categorie::query()
            ->with('categorie')
            ->when($categoryId, function ($query) use ($categoryId) {
                $query->where('categories_id', $categoryId);
            })
            ->orderBy('name')
            ->get()
            ->map(function (Sub_Categorie $subCategory) {
                return [
                    'id' => $subCategory->id,
                    'name' => $subCategory->name,
                    'url_photo' => $subCategory->url_photo,
                    'categories_id' => $subCategory->categories_id,
                    'category_name' => $subCategory->categorie?->name,
                ];
            });

        return response()->json([
            'ok' => true,
            'data' => $subCategories,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules(), $this->messages());

        if ($request->hasFile('url_photo')) {
            $path = $request->file('url_photo')->store('sub_categories', 'public');
            $validated['url_photo'] = '/storage/' . $path;
        }

        Sub_Categorie::create($validated);

        return back()->with('success', 'Subcategoría creada correctamente.');
    }

    public function update(Request $request, Sub_Categorie $subCategory): RedirectResponse
    {
        $validated = $request->validate($this->rules(), $this->messages());

        if ($request->hasFile('url_photo')) {
            $this->deletePublicImage($subCategory->url_photo);

            $path = $request->file('url_photo')->store('sub_categories', 'public');
            $validated['url_photo'] = '/storage/' . $path;
        } else {
            unset($validated['url_photo']);
        }

        $subCategory->update($validated);

        return back()->with('success', 'Subcategoría actualizada correctamente.');
    }

    public function destroy(Sub_Categorie $subCategory): RedirectResponse
    {
        if ($subCategory->products()->exists()) {
            return back()->with('error', 'No puedes eliminar esta subcategoría porque tiene productos registrados.');
        }

        $this->deletePublicImage($subCategory->url_photo);
        $subCategory->delete();

        return back()->with('success', 'Subcategoría eliminada correctamente.');
    }

    private function rules(): array
    {
        return [
            'categories_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'url_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ];
    }

    private function messages(): array
    {
        return [
            'categories_id.required' => 'Debes seleccionar una categoría.',
            'categories_id.exists' => 'La categoría seleccionada no existe.',
            'name.required' => 'El nombre de la subcategoría es obligatorio.',
            'url_photo.image' => 'El archivo debe ser una imagen.',
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
