<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CalcFilament;
use App\Models\CalcMaterial;
use App\Models\Setting;
use Illuminate\Http\Request;

/** Калькулятор цены изделия: фанера/МДФ (материал + резка + гравировка) и/или 3D-печать (граммы филамента × цена за грамм из таблицы) + накладные + наценка */
class CalculatorController extends Controller
{
    private const PARAMS = [
        'calc_waste' => ['min' => 10, 'max' => 100],
        'calc_engr_rate' => ['min' => 0, 'max' => 1000],
        'calc_overhead' => ['min' => 0, 'max' => 500],
        'calc_margin' => ['min' => 0, 'max' => 1000],
        'calc_min_price' => ['min' => 0, 'max' => 1000000],
        'calc_round' => ['min' => 0, 'max' => 1000],
    ];

    public function index()
    {
        return view('admin.calculator', [
            'materials' => CalcMaterial::orderBy('sort')->orderBy('id')->get(),
            'filaments' => CalcFilament::orderBy('sort')->orderBy('id')->get(),
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
            'filaments' => ['nullable', 'array', 'max:100'],
            'filaments.*.id' => ['nullable', 'integer'],
            'filaments.*.name' => ['required', 'string', 'max:100'],
            'filaments.*.spool_price' => ['required', 'numeric', 'min:0', 'max:1000000'],
            'filaments.*.spool_g' => ['required', 'integer', 'min:1', 'max:100000'],
            'filaments.*.link' => ['nullable', 'string', 'max:255'],
            'filaments.*.delete' => ['nullable'],
        ];
        $data = $request->validate($rules, [
            'materials.*.name.required' => 'У каждого материала должно быть название.',
            'materials.*.thickness.required' => 'Укажите толщину материала.',
            'materials.*.sheet_w.required' => 'Укажите размеры листа.',
            'materials.*.sheet_h.required' => 'Укажите размеры листа.',
            'materials.*.sheet_price.required' => 'Укажите цену листа.',
            'materials.*.cut_rate.required' => 'Укажите цену резки за метр.',
            'filaments.*.name.required' => 'У каждого филамента должно быть название.',
            'filaments.*.spool_price.required' => 'Укажите цену катушки филамента.',
            'filaments.*.spool_g.required' => 'Укажите вес катушки в граммах.',
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

        // Филамент: то же правило — дата проверки обновляется только при изменении цены катушки
        foreach (array_values($data['filaments'] ?? []) as $i => $f) {
            if (! empty($f['delete'])) {
                if (! empty($f['id'])) {
                    CalcFilament::whereKey($f['id'])->delete();
                }

                continue;
            }
            $fields = ['name' => $f['name'], 'spool_price' => $f['spool_price'], 'spool_g' => $f['spool_g'], 'link' => $f['link'] ?? null, 'sort' => $i];
            $existing = ! empty($f['id']) ? CalcFilament::find($f['id']) : null;
            if ($existing) {
                if ((float) $existing->spool_price !== (float) $f['spool_price']) {
                    $fields['price_date'] = now()->toDateString();
                }
                $existing->update($fields);
            } else {
                CalcFilament::create($fields + ['price_date' => now()->toDateString()]);
            }
        }

        return redirect()->route('admin.calculator.index')->with('status', 'Параметры калькулятора сохранены.');
    }
}
