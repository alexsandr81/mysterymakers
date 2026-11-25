<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Characteristic;
use App\Models\Holiday;
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
        $query = Product::active()->withRating()->with('category');

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

        // Фильтр по диаметру: ?dia[]=120&dia[]=150 (значения объединяются по «ИЛИ»)
        $diameters = $this->selectedDiameters($request->query('dia'));
        if ($diameters) {
            $query->whereIn('size_diameter', $diameters);
        }

        // Фильтр по праздникам: ?hol[]=<id> (значения объединяются по «ИЛИ»)
        $holidayIds = $this->selectedIds($request->query('hol'));
        if ($holidayIds) {
            $query->whereHas('holidays', fn ($w) => $w->whereIn('holidays.id', $holidayIds));
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
            'diameters' => $this->availableDiameters($category),
            'selectedDiameters' => $diameters,
            'holidays' => $this->availableHolidays($category),
            'selectedHolidays' => $holidayIds,
        ]);
    }

    public function show(Product $product)
    {
        // Скрытые товары видит только администратор (например, из заказа)
        abort_unless($product->is_active || auth()->user()?->is_admin, 404);

        $reviews = $product->approvedReviews()->with('user')->latest()->get();
        $myReview = auth()->check()
            ? $product->reviews()->where('user_id', auth()->id())->first()
            : null;

        return view('catalog.show', [
            'product' => $product->load('category', 'characteristicValues.characteristic'),
            'reviews' => $reviews,
            'ratingAvg' => $reviews->avg('rating'),
            'myReview' => $myReview,
            'related' => Product::active()->withRating()
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

    /** Диаметры (мм), которые есть у активных товаров текущей категории, по возрастанию */
    private function availableDiameters(?Category $category): array
    {
        $query = Product::active()->whereNotNull('size_diameter');
        if ($category) {
            $query->whereIn('category_id', $category->selfAndChildrenIds());
        }

        return $query->distinct()->orderBy('size_diameter')->pluck('size_diameter')->map(fn ($d) => (int) $d)->all();
    }

    /** Праздники, у которых есть активные товары в текущей категории (скрытые не показываем) */
    private function availableHolidays(?Category $category)
    {
        return Holiday::where('is_active', true)
            ->whereHas('products', function ($products) use ($category) {
                $products->where('products.is_active', true);
                if ($category) {
                    $products->whereIn('products.category_id', $category->selfAndChildrenIds());
                }
            })
            ->orderBy('sort')->orderBy('id')->get();
    }

    /** Список положительных целых из параметра адресной строки; мусор игнорируется */
    private function selectedIds(mixed $raw): array
    {
        if (! is_array($raw)) {
            return [];
        }

        return array_values(array_unique(array_map('intval', array_filter($raw, fn ($v) => is_numeric($v) && (int) $v > 0))));
    }

    /** ?dia из адресной строки → список целых чисел; мусор игнорируется */
    private function selectedDiameters(mixed $raw): array
    {
        if (! is_array($raw)) {
            return [];
        }

        return array_values(array_unique(array_map('intval', array_filter($raw, fn ($v) => is_numeric($v) && (int) $v > 0))));
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
