<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetLocale;

class LocaleController extends Controller
{
    public function switch(string $locale)
    {
        abort_unless(in_array($locale, SetLocale::LOCALES, true), 404);
        session(['locale' => $locale]);

        return back();
    }
}
