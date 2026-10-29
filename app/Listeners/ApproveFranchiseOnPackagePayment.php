<?php

namespace App\Listeners;

use App\Actions\ApproveFranchiseHolder;
use App\Enums\OrderType;
use App\Enums\UserStatus;
use App\Events\OrderPaid;

class ApproveFranchiseOnPackagePayment
{
    /**
     * Buying a package makes someone a franchise partner, so approve a pending franchise holder
     * automatically once their package payment succeeds (unless that is switched off in the settings).
     */
    public function handle(OrderPaid $event): void
    {
        $order = $event->order;

        if (! config('franchise.auto_approve_on_package_payment') || $order->type !== OrderType::Package) {
            return;
        }

        $user = $order->user;

        if ($user->isFranchiseHolder() && $user->status === UserStatus::Pending) {
            // The order confirmation email already tells them the payment worked, so no separate approval email.
            app(ApproveFranchiseHolder::class)($user, notify: false);
        }
    }
}
