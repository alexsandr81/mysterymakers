<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Characteristic;
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
        $sort = (string) $request->query('sort', 'created');
        $sort = in_array($sort, ['created', 'name', 'sku', 'category', 'price', 'stock', 'active'], true) ? $sort : 'created';
        $dir = $request->query('dir') === 'asc' ? 'asc' : 'desc';

        $products = Product::with('category')
            ->when($q !== '', function ($query) use ($q) {
                $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $q) . '%';
                $query->where(fn ($w) => $w->where('name_uk', 'like', $like)
                    ->orWhere('name_ru', 'like', $like)
                    ->orWhere('sku', 'like', $like));
            })
            ->when(true, fn ($query) => $this->applySort($query, $sort, $dir))
            ->paginate(20)->withQueryString();

        return view('admin.products.index', compact('products', 'q', 'sort', 'dir'));
    }

    /** Сортировка списка товаров по клику на заголовок колонки (только из разрешённых полей) */
    private function applySort($query, string $sort, string $dir)
    {
        switch ($sort) {
            case 'name':
                $query->orderBy('name_ru', $dir);
                break;
            case 'sku':
                // «999» раньше «1000»: сначала по длине, затем по значению
                $query->orderByRaw('length(sku) ' . $dir)->orderBy('sku', $dir);
                break;
            case 'category':
                $query->orderBy(Category::select('name_ru')->whereColumn('categories.id', 'products.category_id'), $dir);
                break;
            case 'price':
                $query->orderBy('price', $dir);
                break;
            case 'stock':
                $query->orderBy('stock', $dir);
                break;
            case 'active':
                $query->orderBy('is_active', $dir);
                break;
            default:
                $query->orderBy('id', $dir);
        }

        return $query->orderBy('id', 'desc');
    }

    public function create()
    {
        return view('admin.products.form', [
            'product' => new Product(['is_active' => true, 'stock' => 0]),
            'categories' => Category::orderBy('sort')->get(),
            'characteristics' => Characteristic::with('values')->orderBy('sort')->orderBy('id')->get(),
            'selectedValues' => [],
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['name_uk']);
        $new = $this->storeImages($request);
        $data['images'] = $this->arrangeImages($new, $new, (string) $request->input('main_choice', ''));

        $product = Product::create($data);
        $product->characteristicValues()->sync($this->selectedValueIds($request));
        $note = \App\Services\DriveSync::note(\App\Services\DriveSync::product($product));

        return redirect()->route('admin.products.index')->with('status', 'Товар создан.' . $note);
    }

    public function edit(Product $product)
    {
        return view('admin.products.form', [
            'product' => $product,
            'categories' => Category::orderBy('sort')->get(),
            'characteristics' => Characteristic::with('values')->orderBy('sort')->orderBy('id')->get(),
            'selectedValues' => $product->characteristicValues()->pluck('characteristic_values.id')->all(),
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
        $new = $this->storeImages($request);
        $kept = $images->diff($remove)->values()->all();
        $data['images'] = $this->arrangeImages(array_merge($kept, $new), $new, (string) $request->input('main_choice', ''));
        $product->update($data);
        $product->characteristicValues()->sync($this->selectedValueIds($request));
        $note = \App\Services\DriveSync::note(\App\Services\DriveSync::product($product->fresh()));

        return redirect()->route('admin.products.index')->with('status', 'Товар сохранён.' . $note);
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
            'sku' => ['nullable', 'string', 'max:50', Rule::unique('products', 'sku')->ignore($product)],
            'name_uk' => ['required', 'string', 'max:255'],
            'name_ru' => ['required', 'string', 'max:255'],
            'description_uk' => ['nullable', 'string'],
            'description_ru' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0.01', 'max:99999999'],
            'old_price' => ['nullable', 'numeric', 'gt:price', 'max:99999999'],
            'stock' => ['required', 'integer', 'min:0'],
            'size_length' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'size_width' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'size_height' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'size_diameter' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'size_shape' => ['nullable', 'in:rect,round'],
            'main_choice' => ['nullable', 'string', 'max:255'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:20480'],
            'char' => ['nullable', 'array'],
            'char.*' => ['nullable', 'integer', 'exists:characteristic_values,id'],
        ]);

        unset($data['images'], $data['char']);

        // Форма изделия: круглое — только диаметр (длина и ширина обнуляются), прямоугольное — диаметр обнуляется
        if (($data['size_shape'] ?? 'rect') === 'round') {
            $data['size_length'] = $data['size_width'] = null;
        } else {
            $data['size_diameter'] = null;
        }
        unset($data['size_shape']);
        if (empty($data['sku'])) {
            // Пустой артикул: для нового товара — следующий по категории, при редактировании остаётся прежний
            $data['sku'] = $product?->sku ?: \App\Models\Category::findOrFail($data['category_id'])->nextSku();
        }
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }

    /** id выбранных значений характеристик (пустые «— не выбрано —» отбрасываются) */
    private function selectedValueIds(Request $request): array
    {
        return collect((array) $request->input('char', []))
            ->filter(fn ($id) => is_numeric($id) && (int) $id > 0)
            ->map(fn ($id) => (int) $id)
            ->unique()->values()->all();
    }

    /**
     * Ставит выбранное «главное» фото первым (первое фото — обложка в каталоге и на карточке) и оставляет до 5 штук.
     * $choice: путь уже загруженного фото или «new:N» — N-е из только что загруженных.
     *
     * @param  array<int, string>  $all  все фото товара по порядку
     * @param  array<int, string>  $new  только что загруженные
     */
    private function arrangeImages(array $all, array $new, string $choice): array
    {
        $main = str_starts_with($choice, 'new:') ? ($new[(int) substr($choice, 4)] ?? null) : $choice;
        if ($main && in_array($main, $all, true)) {
            $all = array_merge([$main], array_values(array_diff($all, [$main])));
        }

        return array_slice(array_values($all), 0, 5);
    }

    /** @return array<int, string> */
    private function storeImages(Request $request): array
    {
        $paths = [];
        foreach ($request->file('images', []) as $file) {
            $paths[] = \App\Support\ImageStore::store($file, 'products', 1600);
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
