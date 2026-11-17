<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $products = Product::with('category')
            ->when($q !== '', function ($query) use ($q) {
                $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $q) . '%';
                $query->where(fn ($w) => $w->where('name_uk', 'like', $like)
                    ->orWhere('name_ru', 'like', $like)
                    ->orWhere('sku', 'like', $like));
            })
            ->latest()->paginate(20)->withQueryString();

        return view('admin.products.index', compact('products', 'q'));
    }

    public function create()
    {
        return view('admin.products.form', [
            'product' => new Product(['is_active' => true, 'stock' => 0]),
            'categories' => Category::orderBy('sort')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['name_uk']);
        $data['images'] = $this->storeImages($request);

        Product::create($data);

        return redirect()->route('admin.products.index')->with('status', 'Товар создан');
    }

    public function edit(Product $product)
    {
        return view('admin.products.form', [
            'product' => $product,
            'categories' => Category::orderBy('sort')->get(),
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $data = $this->validated($request, $product);

        // Удаляем отмеченные картинки, добавляем новые
        $images = collect($product->images ?? []);
        $remove = (array) $request->input('remove_images', []);
        foreach ($images->intersect($remove) as $path) {
            Storage::disk('public')->delete($path);
        }
        $images = $images->diff($remove)->values()->merge($this->storeImages($request))->take(5)->all();

        $data['images'] = $images;
        $product->update($data);

        return redirect()->route('admin.products.index')->with('status', 'Товар сохранён');
    }

    public function destroy(Product $product)
    {
        foreach ($product->images ?? [] as $path) {
            Storage::disk('public')->delete($path);
        }
        $product->delete();

        return redirect()->route('admin.products.index')->with('status', 'Товар удалён');
    }

    private function validated(Request $request, ?Product $product = null): array
    {
        $data = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'sku' => ['required', 'string', 'max:50', Rule::unique('products', 'sku')->ignore($product)],
            'name_uk' => ['required', 'string', 'max:255'],
            'name_ru' => ['required', 'string', 'max:255'],
            'description_uk' => ['nullable', 'string'],
            'description_ru' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0.01', 'max:99999999'],
            'old_price' => ['nullable', 'numeric', 'gt:price', 'max:99999999'],
            'stock' => ['required', 'integer', 'min:0'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        unset($data['images']);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }

    /** @return array<int, string> */
    private function storeImages(Request $request): array
    {
        $paths = [];
        foreach ($request->file('images', []) as $file) {
            $paths[] = $file->store('products', 'public');
        }

        return array_slice($paths, 0, 5);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name, '-', 'uk') ?: 'product';
        $slug = $base;
        $i = 2;
        while (Product::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }
}
