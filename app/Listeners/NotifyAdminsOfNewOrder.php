<?php

namespace App\Listeners;

use App\Enums\UserRole;
use App\Events\OrderPaid;
use App\Models\User;
use App\Notifications\NewOrderPlaced;
use Illuminate\Support\Facades\Notification;
use Throwable;

class NotifyAdminsOfNewOrder
{
    /**
     * Add a "new order" notification to every admin's dashboard.
     */
    public function handle(OrderPaid $event): void
    {
        try {
            Notification::send(
                User::where('role', UserRole::Admin)->get(),
                new NewOrderPlaced($event->order->loadMissing('user')),
            );
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
