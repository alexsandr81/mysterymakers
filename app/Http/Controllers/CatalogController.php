<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Characteristic;
use App\Models\Product;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    private const SORTS = [
        'new' => ['created_at', 'desc'],
        'price_asc' => ['price', 'asc'],
        'price_desc' => ['price', 'desc'],
    ];

    /** Каталог: все товары, либо категория (/catalog/{slug}), либо поиск (?q=) */
    public function index(Request $request, ?Category $category = null)
    {
        $query = Product::active()->with('category');

        if ($category) {
            $query->whereIn('category_id', $category->selfAndChildrenIds());
        }

        $q = trim((string) $request->query('q', ''));
        if ($q !== '') {
            $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $q) . '%';
            $query->where(function ($w) use ($like) {
                $w->where('name_uk', 'like', $like)
                    ->orWhere('name_ru', 'like', $like)
                    ->orWhere('sku', 'like', $like);
            });
        }

        if (is_numeric($request->query('min'))) {
            $query->where('price', '>=', (float) $request->query('min'));
        }
        if (is_numeric($request->query('max'))) {
            $query->where('price', '<=', (float) $request->query('max'));
        }
        if ($request->boolean('in_stock')) {
            $query->where('stock', '>', 0);
        }

        // Фильтры по характеристикам: ?attr[<id характеристики>][]=<id значения>
        // Внутри одной характеристики значения объединяются по «ИЛИ», между разными — по «И».
        $selected = $this->selectedFilterValues($request->query('attr'));
        foreach ($selected as $valueIds) {
            $query->whereHas('characteristicValues', fn ($w) => $w->whereIn('characteristic_values.id', $valueIds));
        }

        $sort = $request->query('sort', 'new');
        [$column, $direction] = self::SORTS[$sort] ?? self::SORTS['new'];
        $query->orderBy($column, $direction);

        return view('catalog.index', [
            'category' => $category,
            'products' => $query->paginate(12)->withQueryString(),
            'q' => $q,
            'sort' => $sort,
            'filters' => $this->availableFilters($category),
            'selectedValues' => collect($selected)->flatten()->all(),
        ]);
    }

    public function show(Product $product)
    {
        abort_unless($product->is_active, 404);

        return view('catalog.show', [
            'product' => $product->load('category', 'characteristicValues.characteristic'),
            'related' => Product::active()
                ->where('category_id', $product->category_id)
                ->whereKeyNot($product->id)
                ->inRandomOrder()->take(4)->get(),
        ]);
    }

    /**
     * Фильтры для боковой панели: только те значения, которые реально есть
     * у активных товаров текущей категории (чтобы не показывать пустые варианты).
     */
    private function availableFilters(?Category $category)
    {
        return Characteristic::where('is_filterable', true)
            ->orderBy('sort')->orderBy('id')
            ->with(['values' => function ($values) use ($category) {
                $values->whereHas('products', function ($products) use ($category) {
                    $products->where('products.is_active', true);
                    if ($category) {
                        $products->whereIn('products.category_id', $category->selfAndChildrenIds());
                    }
                });
            }])
            ->get()
            ->filter(fn ($c) => $c->values->isNotEmpty())
            ->values();
    }

    /**
     * Приводит ?attr из адресной строки к виду [[1, 2], [5]] — только целые числа.
     * Любой мусор в параметре (строка вместо массива и т. п.) просто игнорируется.
     */
    private function selectedFilterValues(mixed $raw): array
    {
        if (!is_array($raw)) {
            return [];
        }

        $groups = [];
        foreach ($raw as $values) {
            if (!is_array($values)) {
                continue;
            }
            $ids = array_values(array_unique(array_map('intval', array_filter($values, 'is_numeric'))));
            if ($ids) {
                $groups[] = $ids;
            }
        }

        return $groups;
    }
}
