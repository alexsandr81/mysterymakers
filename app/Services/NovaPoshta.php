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

    // ---------- Накладные (ТТН) ----------

    /** Телефон в формате Новой Почты: 380XXXXXXXXX */
    public static function phone(string $phone): string
    {
        $d = preg_replace('/\D+/', '', $phone);
        if (strlen($d) === 10 && $d[0] === '0') {
            $d = '38' . $d;
        }

        return $d;
    }

    /** Отправители (контрагенты) аккаунта */
    public static function senders(): array
    {
        return self::request('Counterparty', 'getCounterparties', ['CounterpartyProperty' => 'Sender'])['data'];
    }

    /** Контактные лица отправителя */
    public static function contacts(string $senderRef): array
    {
        return self::request('Counterparty', 'getCounterpartyContactPersons', ['Ref' => $senderRef])['data'];
    }

    /** Создать получателя; вернёт ['ref' => ..., 'contact' => ...] или ['error' => текст] */
    public static function createRecipient(string $first, string $last, string $phone): array
    {
        $r = self::request('Counterparty', 'save', [
            'FirstName' => $first, 'MiddleName' => '', 'LastName' => $last,
            'Phone' => self::phone($phone), 'Email' => '',
            'CounterpartyType' => 'PrivatePerson', 'CounterpartyProperty' => 'Recipient',
        ]);
        $row = $r['data'][0] ?? null;
        $contact = $row['ContactPerson']['data'][0]['Ref'] ?? null;
        if (!$r['ok'] || !$row || !$contact) {
            return ['error' => self::errorText($r)];
        }

        return ['ref' => $row['Ref'], 'contact' => $contact];
    }

    /** Создать ТТН; вернёт ['ref', 'number', 'cost'] или ['error'] */
    public static function createDocument(array $props): array
    {
        $r = self::request('InternetDocument', 'save', $props);
        $row = $r['data'][0] ?? null;
        if (!$r['ok'] || !$row || empty($row['IntDocNumber'])) {
            return ['error' => self::errorText($r)];
        }

        return ['ref' => $row['Ref'], 'number' => $row['IntDocNumber'], 'cost' => $row['CostOnSite'] ?? null];
    }

    public static function deleteDocument(string $ref): array
    {
        $r = self::request('InternetDocument', 'delete', ['DocumentRefs' => $ref]);

        return $r['ok'] ? [] : ['error' => self::errorText($r)];
    }

    /** Статус посылки: ['code' => , 'text' => ] или null */
    public static function status(string $number, string $phone): ?array
    {
        $r = self::request('TrackingDocument', 'getStatusDocuments', [
            'Documents' => [['DocumentNumber' => $number, 'Phone' => self::phone($phone)]],
        ]);
        $row = $r['data'][0] ?? null;

        return $row ? ['code' => (string) ($row['StatusCode'] ?? ''), 'text' => (string) ($row['Status'] ?? '')] : null;
    }

    /** PDF-наклейка (A4/маркировка) с сервера Новой Почты, содержимое файла или null */
    public static function printPdf(string $number): ?string
    {
        try {
            $res = Http::timeout(20)->get('https://my.novaposhta.ua/orders/printMarking100x100/orders[]/' . urlencode($number) . '/type/pdf/apiKey/' . config('shop.novaposhta.api_key'));

            return $res->successful() && str_starts_with($res->body(), '%PDF') ? $res->body() : null;
        } catch (\Throwable $e) {
            Log::warning('Новая Почта: печать не удалась: ' . $e->getMessage());
            return null;
        }
    }

    private static function errorText(array $r): string
    {
        $parts = array_filter(array_merge($r['errors'] ?? [], $r['warnings'] ?? []));

        return $parts ? implode('; ', $parts) : 'Новая Почта не ответила или вернула пустой результат.';
    }

    /** Вызов API с подробным результатом */
    private static function request(string $model, string $method, array $props): array
    {
        try {
            $res = Http::timeout(20)->post('https://api.novaposhta.ua/v2.0/json/', [
                'apiKey' => config('shop.novaposhta.api_key'),
                'modelName' => $model,
                'calledMethod' => $method,
                'methodProperties' => $props,
            ]);
            $json = $res->json() ?? [];
            if (empty($json['success'])) {
                Log::warning("Новая Почта {$model}.{$method}: " . json_encode($json['errors'] ?? $res->body(), JSON_UNESCAPED_UNICODE));
            }

            return [
                'ok' => !empty($json['success']),
                'data' => $json['data'] ?? [],
                'errors' => (array) ($json['errors'] ?? []),
                'warnings' => (array) ($json['warnings'] ?? []),
            ];
        } catch (\Throwable $e) {
            Log::warning('Новая Почта API недоступен: ' . $e->getMessage());
            return ['ok' => false, 'data' => [], 'errors' => ['API недоступен: ' . $e->getMessage()], 'warnings' => []];
        }
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
