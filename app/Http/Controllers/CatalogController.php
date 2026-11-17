<?php

namespace App\Http\Controllers;

use App\Models\Category;
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

        $sort = $request->query('sort', 'new');
        [$column, $direction] = self::SORTS[$sort] ?? self::SORTS['new'];
        $query->orderBy($column, $direction);

        return view('catalog.index', [
            'category' => $category,
            'products' => $query->paginate(12)->withQueryString(),
            'q' => $q,
            'sort' => $sort,
        ]);
    }

    public function show(Product $product)
    {
        abort_unless($product->is_active, 404);

        return view('catalog.show', [
            'product' => $product->load('category'),
            'related' => Product::active()
                ->where('category_id', $product->category_id)
                ->whereKeyNot($product->id)
                ->inRandomOrder()->take(4)->get(),
        ]);
    }
}
