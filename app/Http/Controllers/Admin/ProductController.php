<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Characteristic;
use App\Models\CharacteristicValue;
use App\Models\Holiday;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $sort = (string) $request->query('sort', 'created');
        $sort = in_array($sort, ['created', 'name', 'sku', 'category', 'sub', 'price', 'stock', 'active'], true) ? $sort : 'created';
        $dir = $request->query('dir') === 'asc' ? 'asc' : 'desc';

        $onlyReview = $request->boolean('review') && $request->user()->is_admin;

        // Без поиска, сортировки и фильтра — товары группами по категориям (раскрываются галочкой)
        $grouped = $q === '' && ! $onlyReview && ! $request->has('sort') && $request->query('view') !== 'list';
        if ($grouped) {
            $all = Product::with(['category.parent', 'modifier'])->orderByRaw('length(sku)')->orderBy('sku')->orderBy('id')->get();
            $products = new \Illuminate\Pagination\LengthAwarePaginator($all, $all->count(), max(1, $all->count()), 1, ['path' => $request->url()]);
            $reviewCount = Product::where('needs_review', true)->count();

            return view('admin.products.index', [
                'products' => $products, 'q' => $q, 'sort' => '', 'dir' => $dir, 'onlyReview' => false, 'reviewCount' => $reviewCount,
                'grouped' => true, 'cats' => Category::orderBy('sort')->orderBy('id')->get(),
                'totalAll' => $all->count(), 'activeAll' => $all->where('is_active', true)->count(),
            ]);
        }

        $products = Product::with(['category.parent', 'modifier'])
            ->when($onlyReview, fn ($query) => $query->where('needs_review', true))
            ->when($q !== '', function ($query) use ($q) {
                $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $q) . '%';
                $query->where(fn ($w) => $w->where('name_uk', 'like', $like)
                    ->orWhere('name_ru', 'like', $like)
                    ->orWhere('sku', 'like', $like));
            })
            ->when(true, fn ($query) => $this->applySort($query, $sort, $dir))
            ->paginate(20)->withQueryString();

        $reviewCount = Product::where('needs_review', true)->count();

        return view('admin.products.index', compact('products', 'q', 'sort', 'dir', 'onlyReview', 'reviewCount') + ['grouped' => false, 'cats' => null, 'totalAll' => Product::count(), 'activeAll' => Product::where('is_active', true)->count()]);
    }

    /** Сортировка списка товаров по клику на заголовок колонки (только из разрешённых полей) */
    private function applySort($query, string $sort, string $dir)
    {
        switch ($sort) {
            case 'name':
                $query->orderBy('name_ru', $dir);
                break;
            case 'sku':
                // «999» раньше «1000»: сначала по длине, затем по значению
                $query->orderByRaw('length(sku) ' . $dir)->orderBy('sku', $dir);
                break;
            case 'category':
                // Категория верхнего уровня: у товара в подкатегории это название родителя
                $query->orderByRaw('(select coalesce(p.name_ru, c.name_ru) from categories c left join categories p on p.id = c.parent_id where c.id = products.category_id) ' . $dir);
                break;
            case 'sub':
                $query->orderByRaw("(select case when c.parent_id is null then '' else c.name_ru end from categories c where c.id = products.category_id) " . $dir);
                break;
            case 'price':
                $query->orderBy('price', $dir);
                break;
            case 'stock':
                $query->orderBy('stock', $dir);
                break;
            case 'active':
                $query->orderBy('is_active', $dir);
                break;
            default:
                $query->orderBy('id', $dir);
        }

        return $query->orderBy('id', 'desc');
    }

    /** Массовое изменение цены у выбранных товаров (или у всех из результатов поиска) */
    public function bulkPrice(Request $request)
    {
        $data = $request->validate([
            'mode' => ['required', 'in:percent_up,percent_down,amount_up,amount_down,set'],
            'value' => ['required', 'numeric', 'gt:0', 'max:99999999'],
            'round' => ['required', 'in:0,1,5,10'],
            'ids' => ['nullable', 'array', 'max:2000'],
            'ids.*' => ['integer'],
            'scope_all' => ['nullable', 'boolean'],
            'q' => ['nullable', 'string', 'max:100'],
        ], [
            'value.required' => 'Введите значение для изменения цены.',
            'value.gt' => 'Значение должно быть больше нуля.',
        ]);

        if ($request->boolean('scope_all')) {
            $q = trim((string) ($data['q'] ?? ''));
            $query = Product::query()->when($q !== '', function ($query) use ($q) {
                $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $q) . '%';
                $query->where(fn ($w) => $w->where('name_uk', 'like', $like)->orWhere('name_ru', 'like', $like)->orWhere('sku', 'like', $like));
            });
        } else {
            if (empty($data['ids'])) {
                return back()->withErrors(['bulk' => 'Отметьте товары галочками слева, у которых нужно изменить цену.']);
            }
            $query = Product::whereIn('id', $data['ids']);
        }

        $value = (float) $data['value'];
        $step = (int) $data['round'];
        $changed = $skipped = 0;

        foreach ($query->get() as $product) {
            $price = (float) $product->price;
            $new = match ($data['mode']) {
                'percent_up' => $price * (1 + $value / 100),
                'percent_down' => $price * (1 - $value / 100),
                'amount_up' => $price + $value,
                'amount_down' => $price - $value,
                default => $value,
            };
            if ($step > 0) {
                $new = round($new / $step) * $step;
            }
            $new = round($new, 2);
            if ($new < 0.01) {
                $skipped++;

                continue;
            }
            $fields = ['price' => $new];
            // Старая цена (для скидки) должна быть выше новой, иначе скидка теряет смысл — убираем её
            if ($product->old_price !== null && (float) $product->old_price <= $new) {
                $fields['old_price'] = null;
            }
            $product->update($fields);
            $changed++;
        }

        $msg = "Цена изменена у товаров: {$changed}.";
        if ($skipped) {
            $msg .= " Пропущено (цена вышла бы ниже 0,01): {$skipped}.";
        }

        return back()->with('status', $msg);
    }

    public function create()
    {
        return view('admin.products.form', [
            'product' => new Product(['is_active' => true, 'stock' => 0]),
            'categories' => Category::orderBy('sort')->get(),
            'characteristics' => Characteristic::with('values')->orderBy('sort')->orderBy('id')->get(),
            'selectedValues' => [],
            'holidays' => Holiday::orderBy('sort')->orderBy('id')->get(),
            'selectedHolidays' => [],
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['name_uk']);
        $new = $this->storeImages($request);
        $data['images'] = $this->arrangeImages($new, $new, (string) $request->input('main_choice', ''));

        $data += $this->reviewFlags($request, null);
        $product = Product::create($data);
        $product->characteristicValues()->sync($this->selectedValueIds($request));
        $this->syncHolidays($product, $request);
        $note = \App\Services\DriveSync::note(\App\Services\DriveSync::product($product));

        return redirect()->route('admin.products.index')->with('status', 'Товар создан.' . $note);
    }

    public function edit(Product $product)
    {
        return view('admin.products.form', [
            'product' => $product,
            'categories' => Category::orderBy('sort')->get(),
            'characteristics' => Characteristic::with('values')->orderBy('sort')->orderBy('id')->get(),
            'selectedValues' => $product->characteristicValues()->pluck('characteristic_values.id')->all(),
            'holidays' => Holiday::orderBy('sort')->orderBy('id')->get(),
            'selectedHolidays' => $product->holidays()->pluck('holidays.id')->all(),
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $data = $this->validated($request, $product);

        // Удаляем отмеченные картинки, добавляем новые
        $images = collect($product->images ?? []);
        $remove = (array) $request->input('remove_images', []);
        foreach ($images->intersect($remove) as $path) {
            Storage::disk('public')->delete($path);
        }
        $new = $this->storeImages($request);
        $kept = $images->diff($remove)->values()->all();
        $data['images'] = $this->arrangeImages(array_merge($kept, $new), $new, (string) $request->input('main_choice', ''));
        $data += $this->reviewFlags($request, $product);
        $oldSku = $product->sku;
        $product->update($data);
        $product->characteristicValues()->sync($this->selectedValueIds($request));
        $this->syncHolidays($product, $request);
        // Папка товара на Google Диске переименуется (она называется «артикул название») и переедет в папку новой категории
        $note = \App\Services\DriveSync::note(\App\Services\DriveSync::product($product->fresh()));
        $skuNote = $oldSku !== $product->sku ? " Артикул изменён: $oldSku → {$product->sku} (по новой категории)." : '';

        return redirect()->route('admin.products.index')->with('status', 'Товар сохранён.' . $skuNote . $note);
    }

    public function destroy(Product $product)
    {
        foreach ($product->images ?? [] as $path) {
            Storage::disk('public')->delete($path);
        }
        $product->delete();

        return redirect()->route('admin.products.index')->with('status', 'Товар удалён');
    }

    private function validated(Request $request, ?Product $product = null): array
    {
        $isAdmin = (bool) $request->user()?->is_admin;
        $rules = [
            // Категория: id, «new» (создать новую); подкатегория (category_id): id, «self» (прямо в категории) или «new»
            'holidays' => ['nullable', 'array', 'max:100'],
            'holidays.*' => ['integer', 'exists:holidays,id'],
            'new_holiday_uk' => ['nullable', 'string', 'max:255', 'required_with:new_holiday_ru'],
            'new_holiday_ru' => ['nullable', 'string', 'max:255', 'required_with:new_holiday_uk'],
            'category_top' => ['nullable', 'string', 'max:20'],
            'category_id' => ['required', 'string', 'max:20'],
            'new_cat_uk' => ['nullable', 'string', 'max:255', 'required_if:category_top,new'],
            'new_cat_ru' => ['nullable', 'string', 'max:255', 'required_if:category_top,new'],
            'new_sub_uk' => ['nullable', 'string', 'max:255', 'required_if:category_id,new'],
            'new_sub_ru' => ['nullable', 'string', 'max:255', 'required_if:category_id,new'],
            'sku' => ['nullable', 'string', 'max:50', Rule::unique('products', 'sku')->ignore($product)],
            'name_uk' => ['required', 'string', 'max:255'],
            'name_ru' => ['required', 'string', 'max:255'],
            'description_uk' => ['nullable', 'string'],
            'description_ru' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0.01', 'max:99999999'],
            'old_price' => ['nullable', 'numeric', 'gt:price', 'max:99999999'],
            'stock' => ['required', 'integer', 'min:0'],
            'size_length' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'size_width' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'size_height' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'size_diameter' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'size_shape' => ['nullable', 'in:rect,round'],
            'main_choice' => ['nullable', 'string', 'max:255'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:20480'],
            'char' => ['nullable', 'array'],
            // Значение характеристики: id существующего или «new» (создать новое, тогда заполняются new_val[id][uk|ru])
            'char.*' => ['nullable', function ($attribute, $value, $fail) {
                if ($value !== 'new' && (! ctype_digit((string) $value) || ! CharacteristicValue::whereKey((int) $value)->exists())) {
                    $fail('Выбрано неизвестное значение характеристики.');
                }
            }],
            'new_val' => ['nullable', 'array'],
            'new_val.*.uk' => ['nullable', 'string', 'max:255'],
            'new_val.*.ru' => ['nullable', 'string', 'max:255'],
            // Новая характеристика целиком: название и первое значение
            'new_char_name_uk' => ['nullable', 'string', 'max:255', 'required_with:new_char_name_ru,new_char_val_uk,new_char_val_ru'],
            'new_char_name_ru' => ['nullable', 'string', 'max:255', 'required_with:new_char_name_uk,new_char_val_uk,new_char_val_ru'],
            'new_char_val_uk' => ['nullable', 'string', 'max:255', 'required_with:new_char_name_uk,new_char_name_ru,new_char_val_ru'],
            'new_char_val_ru' => ['nullable', 'string', 'max:255', 'required_with:new_char_name_uk,new_char_name_ru,new_char_val_uk'],
        ];
        // Модератор может не указывать цену: у нового товара она остаётся пустой («Цена уточняется»), пока её не поставит администратор
        if (! $isAdmin) {
            $rules['price'] = ['nullable', 'numeric', 'min:0.01', 'max:99999999'];
            $rules['old_price'] = ['nullable', 'numeric', 'min:0.01', 'max:99999999'];
        }
        $data = $request->validate($rules);

        // «＋ Новое значение»: у выбранной характеристики должны быть заполнены оба названия
        foreach ((array) $request->input('char', []) as $charId => $val) {
            if ($val === 'new') {
                foreach (['uk' => 'укр.', 'ru' => 'рус.'] as $lg => $label) {
                    if (trim((string) $request->input("new_val.$charId.$lg", '')) === '') {
                        throw \Illuminate\Validation\ValidationException::withMessages(["new_val.$charId.$lg" => "Укажите новое значение характеристики ($label)."]);
                    }
                }
            }
        }

        unset($data['images'], $data['char'], $data['new_val'], $data['new_char_name_uk'], $data['new_char_name_ru'], $data['new_char_val_uk'], $data['new_char_val_ru'], $data['holidays'], $data['new_holiday_uk'], $data['new_holiday_ru']);

        $data['category_id'] = $this->resolveCategory($request, $data);
        unset($data['category_top'], $data['new_cat_uk'], $data['new_cat_ru'], $data['new_sub_uk'], $data['new_sub_ru']);

        // Форма изделия: круглое — только диаметр (длина и ширина обнуляются), прямоугольное — диаметр обнуляется
        if (($data['size_shape'] ?? 'rect') === 'round') {
            $data['size_length'] = $data['size_width'] = null;
        } else {
            $data['size_diameter'] = null;
        }
        unset($data['size_shape']);
        if (empty($data['sku'])) {
            // Артикул стёрли: у нового товара — следующий по категории; у существующего — оставляем, если он уже из блока
            // его категории, иначе (товар перенесли или артикул ручной) присваиваем следующий свободный номер категории
            $target = \App\Models\Category::findOrFail($data['category_id']);
            $start = (int) $target->sku_start;
            $current = (string) ($product?->sku ?? '');
            $inBlock = $current !== '' && ctype_digit($current) && $start > 0 && (int) $current >= $start && (int) $current <= $start + \App\Models\Category::SUB_BLOCK - 1;
            $data['sku'] = $inBlock ? $current : $target->nextSku();
        }
        // Товар переехал в другую категорию (подкатегорию), а артикул не меняли руками — номер должен быть из блока новой категории
        if ($product && (int) $data['category_id'] !== (int) $product->category_id && (string) $data['sku'] === (string) $product->sku && ctype_digit((string) $product->sku)) {
            $target = \App\Models\Category::findOrFail($data['category_id']);
            $start = (int) $target->sku_start;
            $inBlock = $start > 0 && (int) $product->sku >= $start && (int) $product->sku <= $start + \App\Models\Category::SUB_BLOCK - 1;
            if (! $inBlock) {
                $data['sku'] = $target->nextSku();
            }
        }
        if (! $isAdmin) {
            $data['price'] = $data['price'] ?? ($product?->price ?? 0);
            if ((float) $data['price'] <= 0 || (isset($data['old_price']) && (float) $data['old_price'] <= (float) $data['price'])) {
                $data['old_price'] = null; // скидка без цены или с «старой» ценой ниже новой не имеет смысла
            }
        }
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }

    /** Праздники товара: отмеченные галочками + при необходимости новый, созданный прямо в форме */
    private function syncHolidays(Product $product, Request $request): void
    {
        $ids = array_map('intval', (array) $request->input('holidays', []));

        $uk = trim((string) $request->input('new_holiday_uk', ''));
        $ru = trim((string) $request->input('new_holiday_ru', ''));
        if ($uk !== '' && $ru !== '') {
            $existing = Holiday::all()->first(fn ($h) => mb_strtolower($h->name_ru) === mb_strtolower($ru));
            $ids[] = ($existing ?? Holiday::create(['name_uk' => $uk, 'name_ru' => $ru, 'sort' => (int) Holiday::max('sort') + 1, 'is_active' => true]))->id;
        }

        $product->holidays()->sync(array_values(array_unique($ids)));
    }

    /**
     * Метки проверки. Модератор: товар всегда уходит «на проверку» (на сайте остаётся как был).
     * Администратор: метка снимается галочкой «Проверено» (в форме она стоит по умолчанию), иначе остаётся.
     */
    private function reviewFlags(Request $request, ?Product $product): array
    {
        $user = $request->user();
        if (! $user->is_admin) {
            return ['needs_review' => true, 'modified_by' => $user->id]; // каждое изменение модератора проверяет администратор
        }

        return ['needs_review' => (bool) ($product?->needs_review && ! $request->boolean('reviewed'))];
    }

    /** Администратор: «Проверено» для одного товара */
    public function reviewed(Product $product)
    {
        $product->forceFill(['needs_review' => false])->save();

        return back()->with('status', 'Отмечено как проверенное: ' . $product->name_ru);
    }

    /** Администратор: «Проверено» для товаров, отмеченных галочками */
    public function bulkReviewed(Request $request)
    {
        $ids = array_filter((array) $request->input('ids', []), 'is_numeric');
        if (! $ids) {
            return back()->withErrors(['bulk' => 'Отметьте товары галочками слева.']);
        }
        $n = Product::where('needs_review', true)->whereIn('id', $ids)->update(['needs_review' => false]);

        return back()->with('status', "Отмечено проверенными: {$n}.");
    }

    /**
     * Определяет категорию товара по полям формы и при необходимости создаёт новую категорию и/или подкатегорию.
     * Вызывается после проверки остальных полей, чтобы при ошибке в форме пустые категории не создавались.
     */
    private function resolveCategory(Request $request, array $data): int
    {
        $top = $data['category_top'] ?? null;
        $sub = $data['category_id'];

        if ($top === 'new') {
            $parent = $this->createCategory($data['new_cat_uk'], $data['new_cat_ru'], null);
        } elseif ($top !== null && $top !== '' && ctype_digit($top)) {
            $parent = Category::findOrFail((int) $top);
        } elseif (ctype_digit($sub)) {
            // Старый вариант формы: пришла только категория
            return Category::findOrFail((int) $sub)->id;
        } else {
            throw \Illuminate\Validation\ValidationException::withMessages(['category_top' => 'Выберите категорию.']);
        }

        if ($sub === 'new') {
            return $this->createCategory($data['new_sub_uk'], $data['new_sub_ru'], $parent)->id;
        }
        if ($sub === 'self' || (int) $sub === $parent->id) {
            return $parent->id;
        }
        // Подкатегория должна принадлежать выбранной категории
        $child = Category::where('id', (int) $sub)->where('parent_id', $parent->id)->first();
        if (! $child) {
            throw \Illuminate\Validation\ValidationException::withMessages(['category_id' => 'Выберите подкатегорию из списка выбранной категории.']);
        }

        return $child->id;
    }

    private function createCategory(string $uk, string $ru, ?Category $parent): Category
    {
        $start = Category::nextSkuStart($parent?->id);
        if ($start === null) {
            throw \Illuminate\Validation\ValidationException::withMessages(['new_sub_ru' => 'В диапазоне артикулов категории «' . $parent->name_ru . '» больше нет места (максимум 9 подкатегорий).']);
        }

        return Category::create([
            'parent_id' => $parent?->id,
            'slug' => Category::makeSlug($uk),
            'name_uk' => $uk,
            'name_ru' => $ru,
            'sort' => (int) Category::where('parent_id', $parent?->id)->max('sort') + 1,
            'sku_start' => $start,
        ]);
    }

    /** id выбранных значений характеристик (пустые «— не выбрано —» отбрасываются) */
    /** Выбранные значения характеристик; «＋ Новое значение» и «＋ Новая характеристика» создаются здесь */
    private function selectedValueIds(Request $request): array
    {
        $ids = [];
        foreach ((array) $request->input('char', []) as $charId => $val) {
            if ($val === 'new') {
                $id = $this->createValue(Characteristic::find((int) $charId), trim((string) $request->input("new_val.$charId.uk", '')), trim((string) $request->input("new_val.$charId.ru", '')));
                if ($id) {
                    $ids[] = $id;
                }
            } elseif (is_numeric($val) && (int) $val > 0) {
                $ids[] = (int) $val;
            }
        }

        $nameUk = trim((string) $request->input('new_char_name_uk', ''));
        $nameRu = trim((string) $request->input('new_char_name_ru', ''));
        if ($nameUk !== '' && $nameRu !== '') {
            // Характеристика с таким названием уже есть — добавляем значение в неё, а не плодим дубль
            $char = Characteristic::all()->first(fn ($c) => mb_strtolower($c->name_ru) === mb_strtolower($nameRu) || mb_strtolower($c->name_uk) === mb_strtolower($nameUk))
                ?? Characteristic::create(['name_uk' => $nameUk, 'name_ru' => $nameRu, 'is_filterable' => true, 'sort' => (int) Characteristic::max('sort') + 1]);
            $id = $this->createValue($char, trim((string) $request->input('new_char_val_uk', '')), trim((string) $request->input('new_char_val_ru', '')));
            if ($id) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }

    /** Новое значение характеристики (если такое уже есть — берём его) */
    private function createValue(?Characteristic $char, string $uk, string $ru): ?int
    {
        if (! $char || $uk === '' || $ru === '') {
            return null;
        }
        $existing = $char->values()->get()->first(fn ($v) => mb_strtolower($v->value_ru) === mb_strtolower($ru) || mb_strtolower($v->value_uk) === mb_strtolower($uk));
        if ($existing) {
            return $existing->id;
        }

        return CharacteristicValue::create([
            'characteristic_id' => $char->id, 'value_uk' => $uk, 'value_ru' => $ru,
            'sort' => (int) CharacteristicValue::where('characteristic_id', $char->id)->max('sort') + 1,
        ])->id;
    }

    /**
     * Ставит выбранное «главное» фото первым (первое фото — обложка в каталоге и на карточке) и оставляет до 5 штук.
     * $choice: путь уже загруженного фото или «new:N» — N-е из только что загруженных.
     *
     * @param  array<int, string>  $all  все фото товара по порядку
     * @param  array<int, string>  $new  только что загруженные
     */
    private function arrangeImages(array $all, array $new, string $choice): array
    {
        $main = str_starts_with($choice, 'new:') ? ($new[(int) substr($choice, 4)] ?? null) : $choice;
        if ($main && in_array($main, $all, true)) {
            $all = array_merge([$main], array_values(array_diff($all, [$main])));
        }

        return array_slice(array_values($all), 0, 5);
    }

    /** @return array<int, string> */
    private function storeImages(Request $request): array
    {
        $paths = [];
        foreach ($request->file('images', []) as $file) {
            $paths[] = \App\Support\ImageStore::store($file, 'products', 1600);
        }

        return array_slice($paths, 0, 5);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name, '-', 'uk') ?: 'product';
        $slug = $base;
        $i = 2;
        while (Product::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }
}
