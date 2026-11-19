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
            'categories' => Category::with('parent')->withCount('products')->orderBy('sort')->get(),
        ]);
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
