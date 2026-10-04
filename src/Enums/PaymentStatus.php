<?php

namespace ME\Ecom\Enums;

enum PaymentStatus: string
{
    case Unpaid = 'unpaid';
    case Paid = 'paid';
    case Refunded = 'refunded';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::Unpaid => 'warning',
            self::Paid => 'success',
            self::Refunded => 'secondary',
        };
    }
}
