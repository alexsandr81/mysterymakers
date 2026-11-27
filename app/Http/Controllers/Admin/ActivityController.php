<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\User;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    /** Журнал действий модераторов. Открытие страницы снимает счётчик «новое». */
    public function index(Request $request)
    {
        $admin = $request->user();
        $seenAt = $admin->activity_seen_at;
        $userId = (int) $request->query('user');

        $items = Activity::when($userId, fn ($q) => $q->where('user_id', $userId))
            ->latest('id')->paginate(40)->withQueryString();
        $moderators = User::where('is_moderator', true)->orderBy('name')->get(['id', 'name']);

        $admin->forceFill(['activity_seen_at' => now()])->save();

        return view('admin.activity', compact('items', 'moderators', 'userId', 'seenAt'));
    }

    /** Очистить журнал: целиком или (если выбран модератор в фильтре) только его записи */
    public function clear(Request $request)
    {
        $userId = (int) $request->input('user');
        $count = Activity::when($userId, fn ($q) => $q->where('user_id', $userId))->delete();

        return redirect()->route('admin.activity.index', array_filter(['user' => $userId]))
            ->with('status', $count ? "Журнал очищен: удалено записей — $count." : 'Журнал уже пуст.');
    }
}
