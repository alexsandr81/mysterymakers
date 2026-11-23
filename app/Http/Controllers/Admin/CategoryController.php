<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index()
    {
        return view('admin.categories.index', [
            'categories' => $this->tree(),
        ]);
    }

    /** Список «дерево»: родитель, затем его подкатегории; внутри уровня — по порядку сортировки */
    private function tree()
    {
        $all = Category::with('parent')->withCount('products')->orderBy('sort')->orderBy('id')->get();
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
            'category' => new Category(['sort' => 0]),
            'parents' => Category::whereNull('parent_id')->orderBy('sort')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['name_uk']);
        $data['sku_start'] = $data['sku_start'] ?? ((int) Category::max('sku_start') + 1000 ?: 1000);
        Category::create($data);

        return redirect()->route('admin.categories.index')->with('status', 'Категория создана');
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
        $category->update($this->validated($request));

        return redirect()->route('admin.categories.index')->with('status', 'Категория сохранена');
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
