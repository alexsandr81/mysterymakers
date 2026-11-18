<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function index(Request $request)
    {
        return view('account.index', [
            'user' => $request->user(),
            'orders' => $request->user()->orders()->with('items.product')->latest()->get(),
            'favorites' => $request->user()->favorites()->active()->latest('favorites.created_at')->get(),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'regex:/^\+?\d{10,12}$/'],
        ]);

        $request->user()->update($data);

        return back()->with('status', __('shop.saved'));
    }
}
