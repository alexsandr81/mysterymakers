<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CalcMaterial;
use App\Models\Setting;
use Illuminate\Http\Request;

/** Калькулятор цены изделия: фанера/МДФ (материал + резка + гравировка) или 3D-печать (филамент) + накладные + наценка */
class CalculatorController extends Controller
{
    private const PARAMS = [
        'calc_waste' => ['min' => 10, 'max' => 100],
        'calc_engr_cm_rate' => ['min' => 0, 'max' => 10000],
        'calc_filament_price' => ['min' => 0, 'max' => 100],
        'calc_overhead' => ['min' => 0, 'max' => 500],
        'calc_margin' => ['min' => 0, 'max' => 1000],
        'calc_min_price' => ['min' => 0, 'max' => 1000000],
        'calc_round' => ['min' => 0, 'max' => 1000],
    ];

    public function index()
    {
        return view('admin.calculator', [
            'materials' => CalcMaterial::orderBy('sort')->orderBy('id')->get(),
            'p' => collect(array_keys(self::PARAMS))->mapWithKeys(fn ($k) => [$k => (float) Setting::get($k)])->all(),
        ]);
    }

    public function update(Request $request)
    {
        $rules = [];
        foreach (self::PARAMS as $k => $r) {
            $rules[$k] = ['required', 'numeric', 'min:' . $r['min'], 'max:' . $r['max']];
        }
        $rules += [
            'materials' => ['nullable', 'array', 'max:100'],
            'materials.*.id' => ['nullable', 'integer'],
            'materials.*.name' => ['required', 'string', 'max:100'],
            'materials.*.thickness' => ['required', 'numeric', 'min:0.1', 'max:100'],
            'materials.*.sheet_w' => ['required', 'integer', 'min:10', 'max:10000'],
            'materials.*.sheet_h' => ['required', 'integer', 'min:10', 'max:10000'],
            'materials.*.sheet_price' => ['required', 'numeric', 'min:0', 'max:1000000'],
            'materials.*.cut_rate' => ['required', 'numeric', 'min:0', 'max:10000'],
            'materials.*.link' => ['nullable', 'string', 'max:255'],
            'materials.*.delete' => ['nullable'],
        ];
        $data = $request->validate($rules, [
            'materials.*.name.required' => 'У каждого материала должно быть название.',
            'materials.*.thickness.required' => 'Укажите толщину материала.',
            'materials.*.sheet_w.required' => 'Укажите размеры листа.',
            'materials.*.sheet_h.required' => 'Укажите размеры листа.',
            'materials.*.sheet_price.required' => 'Укажите цену листа.',
            'materials.*.cut_rate.required' => 'Укажите цену резки за метр.',
        ]);

        foreach (array_keys(self::PARAMS) as $k) {
            Setting::put($k, (string) $data[$k]);
        }

        $keep = [];
        foreach (array_values($data['materials'] ?? []) as $i => $m) {
            $existing = ! empty($m['id']) ? CalcMaterial::find($m['id']) : null;
            if (! empty($m['delete'])) {
                continue;
            }
            $fields = [
                'name' => $m['name'], 'thickness' => $m['thickness'], 'sheet_w' => $m['sheet_w'], 'sheet_h' => $m['sheet_h'],
                'sheet_price' => $m['sheet_price'], 'cut_rate' => $m['cut_rate'], 'link' => $m['link'] ?? null, 'sort' => $i,
            ];
            if ($existing) {
                // Дату проверки цены обновляем, только когда цена листа изменилась
                if ((float) $existing->sheet_price !== (float) $m['sheet_price']) {
                    $fields['price_date'] = now()->toDateString();
                }
                $existing->update($fields);
                $keep[] = $existing->id;
            } else {
                $keep[] = CalcMaterial::create($fields + ['price_date' => now()->toDateString()])->id;
            }
        }
        // Удаляем только то, что отметили «удалить»
        $removed = collect($data['materials'] ?? [])->filter(fn ($m) => ! empty($m['delete']) && ! empty($m['id']))->pluck('id');
        CalcMaterial::whereIn('id', $removed)->delete();

        return redirect()->route('admin.calculator.index')->with('status', 'Параметры калькулятора сохранены.');
    }
}
