<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PageController extends Controller
{
    public function index()
    {
        return view('admin.pages.index', ['pages' => Page::orderBy('sort')->orderBy('id')->get()]);
    }

    public function create()
    {
        return view('admin.pages.form', [
            'page' => new Page(['is_published' => true, 'show_in_footer' => true, 'sort' => 0]),
        ]);
    }

    public function store(Request $request)
    {
        Page::create($this->validated($request));

        return redirect()->route('admin.pages.index')->with('status', 'Страница создана');
    }

    public function edit(Page $page)
    {
        return view('admin.pages.form', ['page' => $page]);
    }

    public function update(Request $request, Page $page)
    {
        $page->update($this->validated($request, $page));

        return redirect()->route('admin.pages.index')->with('status', 'Страница сохранена');
    }

    public function destroy(Page $page)
    {
        $page->delete();

        return redirect()->route('admin.pages.index')->with('status', 'Страница удалена');
    }

    private function validated(Request $request, ?Page $page = null): array
    {
        $data = $request->validate([
            'slug' => ['required', 'alpha_dash:ascii', 'max:100', Rule::unique('pages', 'slug')->ignore($page)],
            'title_uk' => ['required', 'string', 'max:255'],
            'title_ru' => ['required', 'string', 'max:255'],
            'body_uk' => ['nullable', 'string', 'max:50000'],
            'body_ru' => ['nullable', 'string', 'max:50000'],
            'sort' => ['required', 'integer', 'min:0'],
        ]);

        $data['is_published'] = $request->boolean('is_published');
        $data['show_in_footer'] = $request->boolean('show_in_footer');

        return $data;
    }
}
