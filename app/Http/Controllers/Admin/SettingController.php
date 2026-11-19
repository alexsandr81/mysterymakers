<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function edit()
    {
        return view('admin.settings', [
            'values' => collect(array_keys(Setting::DEFAULTS))->mapWithKeys(fn ($k) => [$k => Setting::get($k)])->all(),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'site_name' => ['required', 'string', 'max:60'],
            'tagline_uk' => ['nullable', 'string', 'max:120'],
            'tagline_ru' => ['nullable', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:300'],
            'email' => ['nullable', 'email', 'max:150'],
            'notify_email' => ['nullable', 'email', 'max:150'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        // Телефоны: до 5 строк, в каждой должен быть номер (10–13 цифр)
        $phones = array_values(array_filter(array_map('trim', preg_split('/\R+/u', $data['phone']))));
        foreach ($phones as $line) {
            $digits = strlen(preg_replace('/\D+/', '', $line));
            if ($digits < 10 || $digits > 13) {
                return back()->withErrors(['phone' => "Не похоже на номер телефона: «{$line}»"])->withInput();
            }
        }
        if (!$phones || count($phones) > 5) {
            return back()->withErrors(['phone' => 'Укажите от 1 до 5 номеров, каждый с новой строки'])->withInput();
        }
        $data['phone'] = implode("\n", $phones);

        foreach ($data as $key => $value) {
            Setting::put($key, $value);
        }

        return back()->with('status', 'Настройки сохранены');
    }
}
