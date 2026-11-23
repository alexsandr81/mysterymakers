<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Services\DriveSync;
use App\Services\GoogleDrive;

/** Кнопка «создать папку на Диске» для уже существующих категорий и товаров */
class DriveController extends Controller
{
    public function category(Category $category)
    {
        return $this->result(GoogleDrive::enabled() ? DriveSync::category($category) : null);
    }

    public function product(Product $product)
    {
        return $this->result(GoogleDrive::enabled() ? DriveSync::product($product) : null);
    }

    private function result(?string $id)
    {
        if (! GoogleDrive::enabled()) {
            return back()->withErrors(['drive' => 'Google Диск не настроен (см. README, раздел 5.4).']);
        }

        return $id
            ? back()->with('status', 'Папка на Google Диске готова')
            : back()->withErrors(['drive' => trim(DriveSync::note($id))]);
    }
}
