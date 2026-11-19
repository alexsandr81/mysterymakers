<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\NovaPoshta;
use Illuminate\Http\Request;

/** Данные отправителя для накладных Новой Почты */
class NovaPoshtaController extends Controller
{
    private const KEYS = ['np_sender_ref', 'np_contact_ref', 'np_sender_phone', 'np_city_ref', 'np_city_title', 'np_wh_ref', 'np_wh_title', 'np_weight', 'np_length', 'np_width', 'np_height', 'np_description'];

    public function edit()
    {
        $enabled = NovaPoshta::enabled();
        $senders = $enabled ? NovaPoshta::senders() : [];
        $senderRef = Setting::get('np_sender_ref') ?: ($senders[0]['Ref'] ?? '');
        $contacts = ($enabled && $senderRef) ? NovaPoshta::contacts($senderRef) : [];

        return view('admin.novaposhta', [
            'enabled' => $enabled,
            'senders' => $senders,
            'contacts' => $contacts,
            'v' => collect(self::KEYS)->mapWithKeys(fn ($k) => [$k => Setting::get($k)])->all() + ['np_sender_ref' => $senderRef],
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'np_sender_ref' => ['required', 'regex:/^[0-9a-f-]{36}$/i'],
            'np_contact_ref' => ['required', 'regex:/^[0-9a-f-]{36}$/i'],
            'np_sender_phone' => ['required', 'regex:/^\+?\d{10,12}$/'],
            'np_city_ref' => ['required', 'regex:/^[0-9a-f-]{36}$/i'],
            'np_city_title' => ['required', 'string', 'max:200'],
            'np_wh_ref' => ['required', 'regex:/^[0-9a-f-]{36}$/i'],
            'np_wh_title' => ['required', 'string', 'max:255'],
            'np_weight' => ['required', 'numeric', 'min:0.1', 'max:1000'],
            'np_length' => ['required', 'numeric', 'min:1', 'max:300'],
            'np_width' => ['required', 'numeric', 'min:1', 'max:300'],
            'np_height' => ['required', 'numeric', 'min:1', 'max:300'],
            'np_description' => ['required', 'string', 'max:100'],
        ], [
            'np_city_ref.required' => 'Выберите город отправки из списка.',
            'np_wh_ref.required' => 'Выберите отделение отправки из списка.',
        ]);

        foreach ($data as $k => $v) {
            Setting::put($k, (string) $v);
        }

        return back()->with('status', 'Данные отправителя сохранены.');
    }
}
