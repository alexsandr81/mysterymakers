<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BannerController extends Controller
{
    public function index()
    {
        return view('admin.banners.index', ['banners' => Banner::orderBy('sort')->orderBy('id')->get()]);
    }

    public function create()
    {
        return view('admin.banners.form', ['banner' => new Banner(['is_active' => true, 'sort' => 0])]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request, true);

        // Можно загрузить сразу до 5 картинок: каждая станет отдельным слайдом
        $request->validate([
            'images' => ['required', 'array', 'min:1', 'max:5'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:20480'],
        ]);

        foreach (array_values($request->file('images')) as $i => $file) {
            Banner::create($data + [
                'image' => \App\Support\ImageStore::store($file, 'banners', 1920),
            ]);
        }

        $count = count($request->file('images'));

        return redirect()->route('admin.banners.index')
            ->with('status', $count > 1 ? "Добавлено баннеров: $count" : 'Баннер создан');
    }

    public function edit(Banner $banner)
    {
        return view('admin.banners.form', ['banner' => $banner]);
    }

    public function update(Request $request, Banner $banner)
    {
        $data = $this->validated($request, false);

        if ($request->hasFile('image')) {
            Storage::disk('public')->delete($banner->image);
            $data['image'] = \App\Support\ImageStore::store($request->file('image'), 'banners', 1920);
        }

        $banner->update($data);

        return redirect()->route('admin.banners.index')->with('status', 'Баннер сохранён');
    }

    public function destroy(Banner $banner)
    {
        Storage::disk('public')->delete($banner->image);
        $banner->delete();

        return redirect()->route('admin.banners.index')->with('status', 'Баннер удалён');
    }

    private function validated(Request $request, bool $imageRequired): array
    {
        $data = $request->validate([
            'title_uk' => ['nullable', 'string', 'max:255'],
            'title_ru' => ['nullable', 'string', 'max:255'],
            'subtitle_uk' => ['nullable', 'string', 'max:255'],
            'subtitle_ru' => ['nullable', 'string', 'max:255'],
            // только http(s):// или путь внутри сайта — чтобы нельзя было подставить javascript:
            'url' => ['nullable', 'string', 'max:255', 'regex:#^(https?://|/)#i'],
            'sort' => ['required', 'integer', 'min:0'],
            'image' => [$imageRequired ? 'prohibited' : 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:20480'],
        ]);

        unset($data['image']);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
