<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public const LOCALES = ['uk', 'ru'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = session('locale', config('app.locale'));
        app()->setLocale(in_array($locale, self::LOCALES, true) ? $locale : 'uk');

        return $next($request);
    }
}
