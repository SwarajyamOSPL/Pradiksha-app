<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Notifications\Notification;

class NewOrderPlaced extends Notification
{
    public function __construct(public Order $order) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array{order_id: int, invoice_number: string|null, customer: string, total: float, items: int}
     */
    public function toArray(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'invoice_number' => $this->order->invoice_number,
            'customer' => $this->order->user->business_name ?: $this->order->user->name,
            'total' => (float) $this->order->total_amount,
            'items' => (int) $this->order->items()->count(),
        ];
    }
}
