<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Setting;
use App\Services\NovaPoshta;

/** Накладные Новой Почты для заказа: создать, обновить статус, распечатать, удалить */
class OrderTtnController extends Controller
{
    public function store(Order $order)
    {
        if (!NovaPoshta::enabled()) {
            return back()->withErrors(['ttn' => 'Не указан NOVAPOSHTA_API_KEY в .env.']);
        }
        if ($order->np_ttn) {
            return back()->withErrors(['ttn' => 'Накладная уже создана.']);
        }
        if ($order->delivery !== 'nova_poshta' || !$order->np_city_ref || !$order->np_warehouse_ref) {
            return back()->withErrors(['ttn' => 'Накладную можно создать только для заказа Новой Почтой с городом и отделением, выбранными из списка.']);
        }
        foreach (['np_sender_ref', 'np_contact_ref', 'np_sender_phone', 'np_city_ref', 'np_wh_ref'] as $k) {
            if (!Setting::get($k)) {
                return back()->withErrors(['ttn' => 'Сначала заполните данные отправителя: Админка → 📦 Новая Почта.']);
            }
        }

        $parts = preg_split('/\s+/u', trim($order->name), 2);
        $first = $parts[0] ?? $order->name;
        $last = $parts[1] ?? $parts[0];

        $recipient = NovaPoshta::createRecipient($first, $last, $order->phone);
        if (isset($recipient['error'])) {
            return back()->withErrors(['ttn' => 'Получатель: ' . $recipient['error']]);
        }

        $total = (float) $order->total;
        $props = [
            'PayerType' => 'Recipient',
            'PaymentMethod' => 'Cash',
            'DateTime' => now()->format('d.m.Y'),
            'CargoType' => 'Cargo',
            'VolumeGeneral' => '0.004',
            'Weight' => (string) Setting::get('np_weight'),
            'ServiceType' => 'WarehouseWarehouse',
            'SeatsAmount' => '1',
            'Description' => Setting::get('np_description'),
            'Cost' => (string) max(1, round($total)),
            'CitySender' => Setting::get('np_city_ref'),
            'Sender' => Setting::get('np_sender_ref'),
            'SenderAddress' => Setting::get('np_wh_ref'),
            'ContactSender' => Setting::get('np_contact_ref'),
            'SendersPhone' => NovaPoshta::phone(Setting::get('np_sender_phone')),
            'CityRecipient' => $order->np_city_ref,
            'Recipient' => $recipient['ref'],
            'RecipientAddress' => $order->np_warehouse_ref,
            'ContactRecipient' => $recipient['contact'],
            'RecipientsPhone' => NovaPoshta::phone($order->phone),
        ];

        // Наложенный платёж: получатель оплачивает товар при получении
        if ($order->payment === 'cod') {
            $props['BackwardDeliveryData'] = [[
                'PayerType' => 'Recipient',
                'CargoType' => 'Money',
                'RedeliveryString' => (string) round($total),
            ]];
        }

        $doc = NovaPoshta::createDocument($props);
        if (isset($doc['error'])) {
            return back()->withErrors(['ttn' => 'Накладная не создана: ' . $doc['error']]);
        }

        $order->update(['np_ttn' => $doc['number'], 'np_ttn_ref' => $doc['ref'], 'np_status' => 'Накладная создана']);

        return back()->with('status', 'Накладная № ' . $doc['number'] . ' создана.');
    }

    public function refresh(Order $order)
    {
        abort_unless($order->np_ttn, 404);

        $st = NovaPoshta::status($order->np_ttn, $order->phone);
        if (!$st) {
            return back()->withErrors(['ttn' => 'Не удалось получить статус от Новой Почты.']);
        }
        $order->update(['np_status' => $st['text']]);

        return back()->with('status', 'Статус обновлён.');
    }

    public function print(Order $order)
    {
        abort_unless($order->np_ttn, 404);

        $pdf = NovaPoshta::printPdf($order->np_ttn);
        if (!$pdf) {
            return back()->withErrors(['ttn' => 'Не удалось получить PDF. Распечатайте накладную в кабинете Новой Почты (my.novaposhta.ua).']);
        }

        return response($pdf, 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="ttn-' . $order->np_ttn . '.pdf"']);
    }

    public function destroy(Order $order)
    {
        abort_unless($order->np_ttn_ref, 404);

        $res = NovaPoshta::deleteDocument($order->np_ttn_ref);
        if (isset($res['error'])) {
            return back()->withErrors(['ttn' => 'Не удалось удалить накладную: ' . $res['error']]);
        }
        $order->update(['np_ttn' => null, 'np_ttn_ref' => null, 'np_status' => null]);

        return back()->with('status', 'Накладная удалена.');
    }
}
