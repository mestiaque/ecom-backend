<?php

namespace ME\Ecom\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Processing = 'processing';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
    case Returned = 'returned';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    /**
     * Bootstrap colour for the status badge.
     */
    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Confirmed => 'info',
            self::Processing => 'primary',
            self::Shipped => 'secondary',
            self::Delivered => 'success',
            self::Cancelled => 'danger',
            self::Returned => 'dark',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Pending => 'fas fa-hourglass-half',
            self::Confirmed => 'fas fa-check',
            self::Processing => 'fas fa-cog',
            self::Shipped => 'fas fa-truck',
            self::Delivered => 'fas fa-check-double',
            self::Cancelled => 'fas fa-times',
            self::Returned => 'fas fa-undo',
        };
    }

    /**
     * Statuses this one may move to: Pending → Confirmed → Processing → Shipped → Delivered,
     * cancel before shipping, return after shipping.
     *
     * @return array<int, self>
     */
    public function allowedNext(): array
    {
        return match ($this) {
            self::Pending => [self::Confirmed, self::Cancelled],
            self::Confirmed => [self::Processing, self::Cancelled],
            self::Processing => [self::Shipped, self::Cancelled],
            self::Shipped => [self::Delivered, self::Returned],
            self::Delivered => [self::Returned],
            self::Cancelled, self::Returned => [],
        };
    }

    public function canMoveTo(self $status): bool
    {
        return in_array($status, $this->allowedNext(), true);
    }

    /**
     * Cancelled and returned orders give their stock back and do not count as sales.
     */
    public function releasesStock(): bool
    {
        return in_array($this, [self::Cancelled, self::Returned], true);
    }

    /**
     * Status values whose orders count towards sales / revenue.
     *
     * @return array<int, string>
     */
    public static function revenueValues(): array
    {
        return array_values(array_map(
            fn (self $status) => $status->value,
            array_filter(self::cases(), fn (self $status) => ! $status->releasesStock())
        ));
    }
}
