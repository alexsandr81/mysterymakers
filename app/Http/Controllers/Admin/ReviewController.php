<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'pending');

        $reviews = Review::with(['product', 'user'])
            ->when(in_array($status, Review::STATUSES, true), fn ($q) => $q->where('status', $status))
            ->latest()->paginate(20)->withQueryString();

        $counts = Review::selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');

        return view('admin.reviews.index', compact('reviews', 'status', 'counts'));
    }

    public function updateStatus(Request $request, Review $review)
    {
        $data = $request->validate(['status' => ['required', 'in:' . implode(',', Review::STATUSES)]]);
        $review->update($data);

        return back()->with('status', 'Статус отзыва обновлён');
    }

    public function destroy(Review $review)
    {
        $review->delete();

        return back()->with('status', 'Отзыв удалён');
    }
}
