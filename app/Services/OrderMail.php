<?php

namespace App\Services;

use App\Mail\OrderPlaced;
use App\Mail\OrderStatusChanged;
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Письма клиенту о заказе. Ошибка почты (нет интернета, неверный пароль SMTP и т. п.)
 * никогда не ломает оформление заказа — она только записывается в лог.
 */
class OrderMail
{
    public static function placed(Order $order): void
    {
        self::send($order, new OrderPlaced($order->loadMissing('items.product')));
    }

    public static function statusChanged(Order $order): void
    {
        if ($order->status === 'new') {
            return;
        }
        self::send($order, new OrderStatusChanged($order->loadMissing('items.product')));
    }

    private static function send(Order $order, $mailable): void
    {
        $to = $order->email ?: $order->user?->email;
        if (!$to) {
            return;
        }

        try {
            Mail::to($to)->locale($order->locale ?: 'uk')->send($mailable);
        } catch (\Throwable $e) {
            Log::error('Не удалось отправить письмо по заказу ' . $order->number . ': ' . $e->getMessage());
        }
    }
}
