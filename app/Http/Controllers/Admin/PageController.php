<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\PageSection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        DB::transaction(function () use ($request) {
            $page = Page::create($this->validated($request));
            $this->syncSections($page, $request);
        });

        return redirect()->route('admin.pages.index')->with('status', 'Страница создана');
    }

    public function edit(Page $page)
    {
        return view('admin.pages.form', ['page' => $page->load('sections')]);
    }

    public function update(Request $request, Page $page)
    {
        DB::transaction(function () use ($request, $page) {
            $page->update($this->validated($request, $page));
            $this->syncSections($page, $request);
        });

        return redirect()->route('admin.pages.index')->with('status', 'Страница сохранена');
    }

    public function destroy(Page $page)
    {
        $page->delete();

        return redirect()->route('admin.pages.index')->with('status', 'Страница удалена');
    }

    /** Разделы страницы: пересоздаются целиком в том порядке, в каком идут в форме. Полностью пустые пропускаются. */
    private function syncSections(Page $page, Request $request): void
    {
        $rows = $request->validate([
            'sections' => ['nullable', 'array', 'max:20'],
            'sections.*.title_uk' => ['nullable', 'string', 'max:255'],
            'sections.*.title_ru' => ['nullable', 'string', 'max:255'],
            'sections.*.body_uk' => ['nullable', 'string', 'max:20000'],
            'sections.*.body_ru' => ['nullable', 'string', 'max:20000'],
        ])['sections'] ?? [];

        $page->sections()->delete();

        $sort = 0;
        foreach ($rows as $row) {
            $row = array_map(fn ($v) => trim((string) $v), $row);
            if (!array_filter($row)) {
                continue;
            }
            $page->sections()->create($row + ['sort' => $sort++]);
        }
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

        $data['body_uk'] = $data['body_uk'] ?? '';
        $data['body_ru'] = $data['body_ru'] ?? '';
        $data['is_published'] = $request->boolean('is_published');
        $data['show_in_footer'] = $request->boolean('show_in_footer');

        return $data;
    }
}
