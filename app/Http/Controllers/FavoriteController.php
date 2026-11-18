<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    /** Добавить товар в избранное или убрать, если он там уже есть */
    public function toggle(Request $request, Product $product)
    {
        $request->user()->favorites()->toggle($product->id);

        return back();
    }
}
