<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function store(Request $request, Product $product)
    {
        abort_unless($product->is_active, 404);

        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'body' => ['required', 'string', 'min:3', 'max:2000'],
        ]);

        $exists = Review::where('product_id', $product->id)->where('user_id', $request->user()->id)->exists();
        if ($exists) {
            return back()->withErrors(['body' => __('shop.review_already')])->withInput();
        }

        // Новый отзыв сначала попадает на модерацию и не виден на сайте
        Review::create($data + [
            'product_id' => $product->id,
            'user_id' => $request->user()->id,
            'status' => 'pending',
        ]);

        return redirect()->to(route('product', $product) . '#reviews')->with('status', __('shop.review_thanks'));
    }
}
