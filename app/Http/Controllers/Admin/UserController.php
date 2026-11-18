<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $users = User::withCount('orders')
            ->when($q !== '', function ($query) use ($q) {
                $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $q) . '%';
                $query->where(fn ($w) => $w->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('phone', 'like', $like));
            })
            ->latest()->paginate(20)->withQueryString();

        return view('admin.users.index', compact('users', 'q'));
    }

    public function edit(User $user)
    {
        return view('admin.users.form', [
            'user' => $user,
            'orders' => $user->orders()->latest()->take(10)->get(),
            'favoritesCount' => $user->favorites()->count(),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'regex:/^\+?\d{10,12}$/'],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        $isAdmin = $request->boolean('is_admin');

        // Нельзя снять права администратора с самого себя и оставить сайт без админов
        if ($user->is_admin && !$isAdmin && ($user->id === $request->user()->id || User::where('is_admin', true)->count() <= 1)) {
            return back()->withErrors(['is_admin' => 'Нельзя снять права администратора у себя или у последнего администратора.'])->withInput();
        }

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user->fill($data);
        $user->is_admin = $isAdmin;
        $user->save();

        return redirect()->route('admin.users.index')->with('status', 'Пользователь сохранён');
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return back()->withErrors(['user' => 'Нельзя удалить самого себя.']);
        }
        if ($user->is_admin && User::where('is_admin', true)->count() <= 1) {
            return back()->withErrors(['user' => 'Нельзя удалить последнего администратора.']);
        }

        // Заказы остаются (user_id станет пустым), избранное удаляется вместе с пользователем
        $user->delete();

        return redirect()->route('admin.users.index')->with('status', 'Пользователь удалён');
    }
}
