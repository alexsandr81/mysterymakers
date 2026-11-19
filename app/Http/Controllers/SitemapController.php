<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Page;
use App\Models\Product;

/** Карта сайта для поисковиков: /sitemap.xml */
class SitemapController extends Controller
{
    public function index()
    {
        $urls = [[route('home'), null], [route('catalog'), null]];

        foreach (Category::all() as $category) {
            $urls[] = [route('catalog.category', $category), $category->updated_at];
        }
        foreach (Product::active()->get(['id', 'slug', 'updated_at']) as $product) {
            $urls[] = [route('product', $product), $product->updated_at];
        }
        foreach (Page::where('is_published', true)->get(['id', 'slug', 'updated_at']) as $page) {
            $urls[] = [route('page', $page), $page->updated_at];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as [$loc, $updated]) {
            $xml .= '  <url><loc>' . e($loc) . '</loc>' . ($updated ? '<lastmod>' . $updated->toAtomString() . '</lastmod>' : '') . "</url>\n";
        }
        $xml .= '</urlset>';

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
