<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Services\DriveSync;
use App\Services\GoogleDrive;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $keys = ['name', 'name_uk', 'parent', 'products', 'sku', 'sort'];
        $sort = in_array($request->query('sort'), $keys, true) ? $request->query('sort') : null;
        $dir = $request->query('dir') === 'desc' ? 'desc' : 'asc';

        // Без сортировки — «дерево» с кнопками ▲▼; с сортировкой по колонке — плоский список
        if (! $sort) {
            return view('admin.categories.index', ['categories' => $this->tree(), 'sort' => null, 'dir' => $dir]);
        }

        $query = Category::with('parent')->withCount(['products', 'children']);
        switch ($sort) {
            case 'name':
                $query->orderBy('name_ru', $dir);
                break;
            case 'name_uk':
                $query->orderBy('name_uk', $dir);
                break;
            case 'parent':
                $query->orderBy(Category::from('categories as c2')->select('c2.name_ru')->whereColumn('c2.id', 'categories.parent_id'), $dir);
                break;
            case 'products':
                $query->orderBy('products_count', $dir);
                break;
            case 'sku':
                $query->orderBy('sku_start', $dir);
                break;
            default:
                $query->orderBy('sort', $dir);
        }
        $categories = $query->orderBy('id')->get();

        return view('admin.categories.index', compact('categories', 'sort', 'dir'));
    }

    /** Список «дерево»: родитель, затем его подкатегории; внутри уровня — по порядку сортировки */
    private function tree()
    {
        $all = Category::with('parent')->withCount(['products', 'children'])->orderBy('sort')->orderBy('id')->get();
        $out = collect();
        foreach ($all->whereNull('parent_id') as $parent) {
            $out->push($parent);
            foreach ($all->where('parent_id', $parent->id) as $child) {
                $out->push($child);
            }
        }
        // На случай подкатегорий без существующего родителя
        foreach ($all->whereNotIn('id', $out->pluck('id')) as $orphan) {
            $out->push($orphan);
        }

        return $out;
    }

    /** Поднять/опустить категорию среди «соседей» (того же родителя) */
    public function move(Category $category, string $direction)
    {
        abort_unless(in_array($direction, ['up', 'down'], true), 404);

        $siblings = Category::where('parent_id', $category->parent_id)->orderBy('sort')->orderBy('id')->get()->values();
        $i = $siblings->search(fn ($s) => $s->id === $category->id);
        $j = $direction === 'up' ? $i - 1 : $i + 1;

        if ($i !== false && isset($siblings[$j])) {
            $order = $siblings->all();
            [$order[$i], $order[$j]] = [$order[$j], $order[$i]];
            // Перенумеровываем весь уровень, чтобы порядок был однозначным
            foreach ($order as $n => $cat) {
                if ((int) $cat->sort !== $n) {
                    $cat->update(['sort' => $n]);
                }
            }
        }

        return back();
    }

    public function create()
    {
        return view('admin.categories.form', [
            'category' => new Category(['sort' => 0, 'parent_id' => request()->integer('parent_id') ?: null]),
            'parents' => Category::whereNull('parent_id')->orderBy('sort')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['name_uk']);
        $data['sku_start'] = $data['sku_start'] ?? Category::nextSkuStart($data['parent_id'] ?? null);
        if ($data['sku_start'] === null) {
            return back()->withInput()->withErrors(['parent_id' => 'В диапазоне артикулов родительской категории больше нет места (максимум 9 подкатегорий). Укажите начало артикулов вручную.']);
        }
        $category = Category::create($data);
        $note = DriveSync::note(DriveSync::category($category));

        return redirect()->route('admin.categories.index')->with('status', 'Категория создана.' . $note);
    }

    public function edit(Category $category)
    {
        return view('admin.categories.form', [
            'category' => $category,
            'parents' => Category::whereNull('parent_id')->whereKeyNot($category->id)->orderBy('sort')->get(),
        ]);
    }

    public function update(Request $request, Category $category)
    {
        $data = $this->validated($request);
        $parentChanged = (int) ($data['parent_id'] ?? 0) !== (int) $category->parent_id;
        $oldStart = (int) $category->sku_start;
        $explicit = ! empty($data['sku_start']);
        // Пусто или родитель сменился, а число не трогали — подбираем блок заново (артикулы уже созданных товаров не меняются)
        if (empty($data['sku_start']) || ($parentChanged && (int) $data['sku_start'] === (int) $category->sku_start)) {
            $data['sku_start'] = Category::nextSkuStart($data['parent_id'] ?? null) ?? $category->sku_start;
        }

        // Начало артикулов изменили (родитель тот же): сдвигаем на столько же подкатегории и артикулы товаров блока
        $delta = (! $parentChanged && $explicit && $oldStart > 0 && (int) $data['sku_start'] !== $oldStart) ? (int) $data['sku_start'] - $oldStart : 0;
        $plan = null;
        if ($delta !== 0) {
            $plan = $this->planSkuShift($category, $oldStart, $delta);
            if ($plan['error']) {
                return back()->withInput()->withErrors(['sku_start' => $plan['error']]);
            }
        }

        DB::transaction(function () use ($category, $data, $plan, $delta) {
            $category->update($data);
            if ($plan) {
                foreach ($plan['children'] as $child) {
                    Category::whereKey($child->id)->update(['sku_start' => (int) $child->sku_start + $delta]);
                }
                foreach ($plan['products'] as $p) { // порядок подобран так, чтобы новые артикулы не сталкивались со старыми
                    Product::whereKey($p->id)->update(['sku' => (string) ((int) $p->sku + $delta)]);
                }
            }
        });
        $note = DriveSync::note(DriveSync::category($category));

        $shifted = '';
        if ($plan) {
            // Папки товаров на Google Диске называются «артикул название» — переименовываем
            @set_time_limit(300);
            $failed = 0;
            foreach ($plan['products'] as $p) {
                $product = Product::with('category')->find($p->id);
                if ($product && ! DriveSync::product($product) && GoogleDrive::enabled()) {
                    $failed++;
                }
            }
            $shifted = sprintf(' Артикулы сдвинуты на %+d: подкатегорий — %d, товаров — %d.', $delta, $plan['children']->count(), $plan['products']->count())
                . ($failed ? " Папки на Google Диске не переименованы у товаров: $failed (подробности: storage/logs/laravel.log; команда shop:drive-sync исправит их позже)." : '');
        }

        return redirect()->route('admin.categories.index')->with('status', 'Категория сохранена.' . $shifted . $note);
    }

    /**
     * Что изменится, если сдвинуть начало артикулов категории на $delta: её подкатегории (у верхнего уровня)
     * и товары с числовыми артикулами из старого блока. Вернёт ошибку, если новый диапазон занят.
     */
    private function planSkuShift(Category $category, int $old, int $delta): array
    {
        $size = $category->skuBlockSize();
        $oldEnd = $old + $size - 1;
        $new = $old + $delta;
        $newEnd = $new + $size - 1;

        $children = $category->parent_id ? collect() : $category->children()->whereNotNull('sku_start')->get();
        $ids = array_merge([$category->id], $children->pluck('id')->all());
        $products = Product::whereIn('category_id', $ids)->get(['id', 'sku'])
            ->filter(fn ($p) => ctype_digit((string) $p->sku) && (int) $p->sku >= $old && (int) $p->sku <= $oldEnd)
            ->sortBy(fn ($p) => (int) $p->sku * ($delta > 0 ? -1 : 1))->values();

        $result = ['error' => null, 'children' => $children, 'products' => $products];

        $siblings = Category::whereKeyNot($category->id)->whereNotNull('sku_start')
            ->when($category->parent_id, fn ($q) => $q->where('parent_id', $category->parent_id), fn ($q) => $q->whereNull('parent_id'))
            ->pluck('sku_start');
        foreach ($siblings as $s) {
            if ($new <= $s + $size - 1 && $s <= $newEnd) {
                $result['error'] = "Новый блок артикулов $new–$newEnd пересекается с блоком другой категории (начало $s). Выберите другое число.";

                return $result;
            }
        }

        $clash = Product::whereNotIn('id', $products->pluck('id'))->pluck('sku')
            ->first(fn ($s) => ctype_digit((string) $s) && (int) $s >= $new && (int) $s <= $newEnd);
        if ($clash !== null) {
            $result['error'] = "В новом диапазоне $new–$newEnd уже есть артикул $clash другого товара. Выберите другое число.";
        }

        return $result;
    }

    public function destroy(Category $category)
    {
        if ($category->products()->exists() || $category->children()->exists()) {
            return back()->withErrors(['category' => 'Нельзя удалить категорию, в которой есть товары или подкатегории.']);
        }
        $category->delete();

        return redirect()->route('admin.categories.index')->with('status', 'Категория удалена');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name_uk' => ['required', 'string', 'max:255'],
            'name_ru' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'exists:categories,id'],
            'sort' => ['required', 'integer', 'min:0'],
            'sku_start' => ['nullable', 'integer', 'min:1', 'max:99999999'],
        ]);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name, '-', 'uk') ?: 'category';
        $slug = $base;
        $i = 2;
        while (Category::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }
}
