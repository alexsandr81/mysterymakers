<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Setting;
use App\Services\NovaPoshta;
use Illuminate\Http\Request;

/** Накладные Новой Почты для заказа: создать, обновить статус, распечатать, удалить */
class OrderTtnController extends Controller
{
    public function store(Request $request, Order $order)
    {
        $in = $request->validate([
            'weight' => ['required', 'numeric', 'min:0.1', 'max:1000'],
            'length' => ['required', 'numeric', 'min:1', 'max:300'],
            'width' => ['required', 'numeric', 'min:1', 'max:300'],
            'height' => ['required', 'numeric', 'min:1', 'max:300'],
            'payer' => ['required', 'in:Recipient,Sender'],
            'first_name' => ['required', 'string', 'max:60', "regex:/^[\p{Cyrillic}'’\- ]+$/u"],
            'last_name' => ['required', 'string', 'max:60', "regex:/^[\p{Cyrillic}'’\- ]+$/u"],
        ], [
            'first_name.regex' => 'Новая Почта принимает имя получателя только кириллицей (укр./рус.). Впишите имя кириллицей в форме накладной.',
            'last_name.regex' => 'Новая Почта принимает фамилию получателя только кириллицей (укр./рус.). Впишите фамилию кириллицей в форме накладной.',
        ]);

        if (!NovaPoshta::enabled()) {
            return back()->withErrors(['ttn' => 'Не указан NOVAPOSHTA_API_KEY в .env.']);
        }
        if ($order->np_ttn) {
            return back()->withErrors(['ttn' => 'Накладная уже создана.']);
        }
        if ($order->payment === 'card' && !$order->paid_at) {
            return back()->withErrors(['ttn' => 'Заказ с оплатой на карту ещё не оплачен. Сначала получите оплату и нажмите «Отметить оплаченным».']);
        }

        $toDoor = $order->delivery === 'np_address';
        $ready = $toDoor
            ? ($order->np_city_ref && $order->np_street_ref && $order->np_building)
            : ($order->delivery === 'nova_poshta' && $order->np_city_ref && $order->np_warehouse_ref);
        if (!$ready) {
            return back()->withErrors(['ttn' => 'Накладную можно создать только для заказа Новой Почтой, где город и отделение (или улица) выбраны из списка.']);
        }
        if ($order->np_is_postomat && in_array($order->payment, \App\Models\Order::NO_POSTOMAT_PAYMENT, true)) {
            return back()->withErrors(['ttn' => 'В почтомат нельзя отправить с наложенным платежом. Свяжитесь с клиентом и предложите отделение (или предоплату), а в заказе обновите данные доставки.']);
        }
        if ($order->np_is_postomat && (float) $in['weight'] > 20) {
            return back()->withErrors(['ttn' => 'В почтомат Новой Почты нельзя отправить больше 20 кг — уменьшите вес или выберите отделение.'])->withInput();
        }
        foreach (['np_sender_ref', 'np_contact_ref', 'np_sender_phone', 'np_city_ref', 'np_wh_ref'] as $k) {
            if (!Setting::get($k)) {
                return back()->withErrors(['ttn' => 'Сначала заполните данные отправителя: Админка → 📦 Новая Почта.']);
            }
        }

        $recipient = NovaPoshta::createRecipient(trim($in['first_name']), trim($in['last_name']), $order->phone);
        if (isset($recipient['error'])) {
            return back()->withErrors(['ttn' => 'Получатель: ' . $recipient['error']]);
        }

        // Адресная доставка: создаём адрес получателя (улица, дом, квартира)
        $addressRef = $order->np_warehouse_ref;
        if ($toDoor) {
            $addr = NovaPoshta::createAddress($recipient['ref'], $order->np_street_ref, (string) $order->np_building, $order->np_flat);
            if (isset($addr['error'])) {
                return back()->withErrors(['ttn' => 'Адрес получателя: ' . $addr['error']]);
            }
            $addressRef = $addr['ref'];
        }

        $total = (float) $order->total;
        $props = [
            'PayerType' => $in['payer'],
            'PaymentMethod' => 'Cash',
            'DateTime' => now('Europe/Kyiv')->format('d.m.Y'), // дата по Киеву, а не по часовому поясу сервера
            'CargoType' => 'Cargo',
            'Weight' => (string) $in['weight'],
            'ServiceType' => $toDoor ? 'WarehouseDoors' : 'WarehouseWarehouse',
            'SeatsAmount' => '1',
            'OptionsSeat' => [[
                'volumetricLength' => (string) $in['length'],
                'volumetricWidth' => (string) $in['width'],
                'volumetricHeight' => (string) $in['height'],
                'weight' => (string) $in['weight'],
            ]],
            'Description' => Setting::get('np_description'),
            'Cost' => (string) max(1, round($total)),
            'CitySender' => Setting::get('np_city_ref'),
            'Sender' => Setting::get('np_sender_ref'),
            'SenderAddress' => Setting::get('np_wh_ref'),
            'ContactSender' => Setting::get('np_contact_ref'),
            'SendersPhone' => NovaPoshta::phone(Setting::get('np_sender_phone')),
            'CityRecipient' => $order->np_city_ref,
            'Recipient' => $recipient['ref'],
            'RecipientAddress' => $addressRef,
            'ContactRecipient' => $recipient['contact'],
            'RecipientsPhone' => NovaPoshta::phone($order->phone),
        ];

        // Обратная доставка денег — только при наложенном платеже (при оплате картой не добавляется)
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
