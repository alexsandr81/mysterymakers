<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Вход через Google (OAuth 2.0), без дополнительных пакетов.
 * Нужны GOOGLE_CLIENT_ID и GOOGLE_CLIENT_SECRET в .env.
 */
class GoogleAuthController extends Controller
{
    public function redirect(Request $request)
    {
        abort_unless(config('shop.google.client_id') && config('shop.google.client_secret'), 404);

        $state = Str::random(40);
        $request->session()->put('google_state', $state);

        return redirect('https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
            'client_id' => config('shop.google.client_id'),
            'redirect_uri' => route('google.callback'),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'prompt' => 'select_account',
        ]));
    }

    public function callback(Request $request)
    {
        abort_unless(config('shop.google.client_id') && config('shop.google.client_secret'), 404);

        $expected = $request->session()->pull('google_state');
        if (!$request->filled('code') || !$expected || !hash_equals($expected, (string) $request->query('state'))) {
            return $this->fail();
        }

        try {
            $token = Http::asForm()->timeout(15)->post('https://oauth2.googleapis.com/token', [
                'code' => $request->query('code'),
                'client_id' => config('shop.google.client_id'),
                'client_secret' => config('shop.google.client_secret'),
                'redirect_uri' => route('google.callback'),
                'grant_type' => 'authorization_code',
            ]);

            if (!$token->successful()) {
                report(new \RuntimeException('Google token error: ' . $token->body()));
                return $this->fail();
            }

            $info = Http::withToken($token->json('access_token'))->timeout(15)
                ->get('https://openidconnect.googleapis.com/v1/userinfo');
        } catch (\Throwable $e) {
            report($e);
            return $this->fail();
        }

        $googleId = $info->json('sub');
        $email = $info->json('email');
        if (!$info->successful() || !$googleId || !$email || !$info->json('email_verified')) {
            return $this->fail();
        }

        $user = User::where('google_id', $googleId)->first()
            ?? User::where('email', $email)->first();

        if ($user) {
            if (!$user->google_id) {
                $user->google_id = $googleId;
                $user->save();
            }
        } else {
            $user = new User([
                'name' => $info->json('name') ?: Str::before($email, '@'),
                'email' => $email,
                'password' => Str::random(40), // входит через Google; пароль не нужен
            ]);
            $user->google_id = $googleId;
            $user->save();
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->intended(route('account'));
    }

    private function fail()
    {
        return redirect()->route('login')->withErrors(['email' => __('shop.google_error')]);
    }
}
