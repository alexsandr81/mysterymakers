<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;

class HomeController extends Controller
{
    public function index()
    {
        return view('home', [
            'banners' => Banner::where('is_active', true)->orderBy('sort')->orderBy('id')->get(),
            'categories' => Category::whereNull('parent_id')->orderBy('sort')->get(),
            'sale' => Product::active()->whereNotNull('old_price')->whereColumn('old_price', '>', 'price')->latest()->take(4)->get(),
            'latest' => Product::active()->latest()->take(8)->get(),
        ]);
    }
}
