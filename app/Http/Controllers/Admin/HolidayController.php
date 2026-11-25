<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use Illuminate\Http\Request;

class HolidayController extends Controller
{
    public function index()
    {
        return view('admin.holidays.index', [
            'holidays' => Holiday::withCount('products')->orderBy('sort')->orderBy('id')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.holidays.form', ['holiday' => new Holiday(['is_active' => true, 'sort' => (int) Holiday::max('sort') + 1])]);
    }

    public function store(Request $request)
    {
        Holiday::create($this->validated($request));

        return redirect()->route('admin.holidays.index')->with('status', 'Праздник добавлен');
    }

    public function edit(Holiday $holiday)
    {
        return view('admin.holidays.form', ['holiday' => $holiday]);
    }

    public function update(Request $request, Holiday $holiday)
    {
        $holiday->update($this->validated($request));

        return redirect()->route('admin.holidays.index')->with('status', 'Праздник сохранён');
    }

    public function destroy(Holiday $holiday)
    {
        $holiday->delete(); // привязки к товарам удаляются каскадом, сами товары остаются

        return redirect()->route('admin.holidays.index')->with('status', 'Праздник удалён');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name_uk' => ['required', 'string', 'max:255'],
            'name_ru' => ['required', 'string', 'max:255'],
            'sort' => ['required', 'integer', 'min:0'],
        ]);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
