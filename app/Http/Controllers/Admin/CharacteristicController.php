<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Characteristic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CharacteristicController extends Controller
{
    public function index()
    {
        return view('admin.characteristics.index', [
            'characteristics' => Characteristic::with('values')->orderBy('sort')->orderBy('id')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.characteristics.form', [
            'characteristic' => new Characteristic(['is_filterable' => true, 'sort' => 0]),
            'valuesText' => '',
        ]);
    }

    public function store(Request $request)
    {
        [$data, $items] = $this->validated($request);

        DB::transaction(function () use ($data, $items) {
            $this->syncValues(Characteristic::create($data), $items);
        });

        return redirect()->route('admin.characteristics.index')->with('status', 'Характеристика создана');
    }

    public function edit(Characteristic $characteristic)
    {
        return view('admin.characteristics.form', [
            'characteristic' => $characteristic,
            'valuesText' => $characteristic->values
                ->map(fn ($v) => $v->value_uk . ' | ' . $v->value_ru)
                ->implode("\n"),
        ]);
    }

    public function update(Request $request, Characteristic $characteristic)
    {
        [$data, $items] = $this->validated($request);

        DB::transaction(function () use ($characteristic, $data, $items) {
            $characteristic->update($data);
            $this->syncValues($characteristic, $items);
        });

        return redirect()->route('admin.characteristics.index')->with('status', 'Характеристика сохранена');
    }

    public function destroy(Characteristic $characteristic)
    {
        $characteristic->delete(); // значения и привязки к товарам удалятся каскадом

        return redirect()->route('admin.characteristics.index')->with('status', 'Характеристика удалена');
    }

    /** @return array{0: array, 1: array} */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name_uk' => ['required', 'string', 'max:255'],
            'name_ru' => ['required', 'string', 'max:255'],
            'sort' => ['required', 'integer', 'min:0'],
            'values' => ['nullable', 'string', 'max:5000'],
        ]);

        $items = $this->parseValues((string) ($data['values'] ?? ''));
        unset($data['values']);
        $data['is_filterable'] = $request->boolean('is_filterable');

        return [$data, $items];
    }

    /**
     * Разбирает текст: одна строка = одно значение, формат «укр | рус».
     * Если после | ничего нет, русский вариант = украинскому.
     *
     * @return array<int, array{uk: string, ru: string}>
     */
    private function parseValues(string $text): array
    {
        $items = [];
        foreach (preg_split('/\R/u', $text) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $parts = array_map('trim', explode('|', $line, 2));
            $uk = $parts[0];
            $ru = ($parts[1] ?? '') !== '' ? $parts[1] : $uk;
            if ($uk !== '') {
                $items[mb_strtolower($uk)] = ['uk' => $uk, 'ru' => $ru]; // дубликаты схлопываются
            }
        }

        return array_values($items);
    }

    /**
     * Обновляет значения, не теряя привязки к товарам: существующие
     * сопоставляются по украинскому названию, лишние удаляются.
     */
    private function syncValues(Characteristic $characteristic, array $items): void
    {
        $existing = $characteristic->values()->get()->keyBy(fn ($v) => mb_strtolower($v->value_uk));
        $keep = [];

        foreach ($items as $i => $item) {
            $found = $existing->get(mb_strtolower($item['uk']));
            if ($found) {
                $found->update(['value_uk' => $item['uk'], 'value_ru' => $item['ru'], 'sort' => $i]);
                $keep[] = $found->id;
            } else {
                $keep[] = $characteristic->values()->create([
                    'value_uk' => $item['uk'], 'value_ru' => $item['ru'], 'sort' => $i,
                ])->id;
            }
        }

        $characteristic->values()->whereNotIn('id', $keep)->delete();
    }
}
