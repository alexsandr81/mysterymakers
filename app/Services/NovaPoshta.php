<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Справочники Новой Почты (города и отделения) через официальный API.
 * Нужен бесплатный ключ в .env: NOVAPOSHTA_API_KEY. Без ключа форма заказа
 * показывает обычные текстовые поля вместо выбора из списка.
 */
class NovaPoshta
{
    public static function enabled(): bool
    {
        return (bool) config('shop.novaposhta.api_key');
    }

    /** @return array<int, array{ref: string, title: string}> */
    public static function cities(string $query): array
    {
        $query = trim($query);
        if (mb_strlen($query) < 2) {
            return [];
        }

        return Cache::remember('np:cities:' . md5(mb_strtolower($query)), 3600, function () use ($query) {
            $data = self::call('Address', 'searchSettlements', ['CityName' => $query, 'Limit' => 10, 'Page' => 1]);

            $out = [];
            foreach ($data[0]['Addresses'] ?? [] as $a) {
                if (empty($a['DeliveryCity']) || (int) ($a['Warehouses'] ?? 0) < 1) {
                    continue; // нет отделений — доставить Новой Почтой туда нельзя
                }
                $out[] = ['ref' => $a['DeliveryCity'], 'title' => $a['Present']];
            }

            return $out;
        });
    }

    /** @return array<int, array{ref: string, title: string}> */
    public static function warehouses(string $cityRef, string $query, string $locale): array
    {
        $query = trim($query);
        $lang = $locale === 'ru' ? 'RU' : 'UA';

        return Cache::remember('np:wh:' . md5($cityRef . '|' . mb_strtolower($query) . '|' . $lang), 3600, function () use ($cityRef, $query, $lang) {
            $props = ['CityRef' => $cityRef, 'Limit' => 40, 'Page' => 1, 'Language' => $lang];
            if ($query !== '') {
                $props['FindByString'] = $query;
            }

            $out = [];
            foreach (self::call('Address', 'getWarehouses', $props) as $w) {
                $title = $lang === 'RU' ? ($w['DescriptionRu'] ?? $w['Description']) : $w['Description'];
                $out[] = ['ref' => $w['Ref'], 'title' => $title];
            }

            return $out;
        });
    }

    private static function call(string $model, string $method, array $props): array
    {
        try {
            $res = Http::timeout(10)->post('https://api.novaposhta.ua/v2.0/json/', [
                'apiKey' => config('shop.novaposhta.api_key'),
                'modelName' => $model,
                'calledMethod' => $method,
                'methodProperties' => $props,
            ]);

            if (!$res->successful() || !$res->json('success')) {
                Log::warning('Новая Почта API: ' . json_encode($res->json('errors') ?? $res->body(), JSON_UNESCAPED_UNICODE));
                return [];
            }

            return $res->json('data') ?? [];
        } catch (\Throwable $e) {
            Log::warning('Новая Почта API недоступен: ' . $e->getMessage());
            return [];
        }
    }
}
