<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Processing = 'processing';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Confirmed => 'Confirmed',
            self::Processing => 'Processing',
            self::Shipped => 'Shipped',
            self::Delivered => 'Delivered',
            self::Cancelled => 'Cancelled',
        };
    }

    /**
     * A plain-language line telling the customer where their order is.
     */
    public function customerMessage(): string
    {
        return match ($this) {
            self::Pending => 'Waiting for payment',
            self::Confirmed => 'Order confirmed, we are preparing it',
            self::Processing => 'Your order is being packed',
            self::Shipped => 'In transit, on its way to you',
            self::Delivered => 'Delivered',
            self::Cancelled => 'This order was cancelled',
        };
    }

    /**
     * Whether the order is paid for but not yet delivered or cancelled.
     */
    public function isInProgress(): bool
    {
        return in_array($this, [self::Confirmed, self::Processing, self::Shipped], true);
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::Confirmed => 'sky',
            self::Processing => 'violet',
            self::Shipped => 'blue',
            self::Delivered => 'green',
            self::Cancelled => 'red',
        };
    }
}
