<?php

namespace App\Http\Controllers;

use App\Services\NovaPoshta;
use Illuminate\Http\Request;

/** Подсказки городов и отделений Новой Почты для формы заказа */
class NovaPoshtaController extends Controller
{
    public function cities(Request $request)
    {
        abort_unless(NovaPoshta::enabled(), 404);
        $data = $request->validate(['q' => ['required', 'string', 'max:100']]);

        return response()->json(NovaPoshta::cities($data['q']));
    }

    public function streets(Request $request)
    {
        abort_unless(NovaPoshta::enabled(), 404);
        $data = $request->validate([
            'settlement' => ['required', 'regex:/^[0-9a-f-]{36}$/i'],
            'q' => ['required', 'string', 'max:100'],
        ]);

        return response()->json(NovaPoshta::streets($data['settlement'], $data['q']));
    }

    public function warehouses(Request $request)
    {
        abort_unless(NovaPoshta::enabled(), 404);
        $data = $request->validate([
            'city' => ['required', 'regex:/^[0-9a-f-]{36}$/i'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        return response()->json(NovaPoshta::warehouses($data['city'], $data['q'] ?? '', app()->getLocale()));
    }
}
