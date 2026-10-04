<?php

namespace ME\Ecom\Enums;

enum PaymentMethod: string
{
    case CashOnDelivery = 'cod';
    case Bkash = 'bkash';
    case Nagad = 'nagad';
    case Sslcommerz = 'sslcommerz';

    public function label(): string
    {
        return match ($this) {
            self::CashOnDelivery => 'Cash on Delivery',
            self::Bkash => 'bKash',
            self::Nagad => 'Nagad',
            self::Sslcommerz => 'Card (SSLCommerz)',
        };
    }

    /**
     * Gateway credential fields shown on the Payment Methods page.
     * Fields marked secret are stored encrypted and never echoed back to the form.
     *
     * @return array<string, array{label: string, secret: bool}>
     */
    public function credentialFields(): array
    {
        return match ($this) {
            self::CashOnDelivery => [],
            self::Bkash => [
                'app_key' => ['label' => 'App Key', 'secret' => false],
                'app_secret' => ['label' => 'App Secret', 'secret' => true],
                'username' => ['label' => 'Username', 'secret' => false],
                'password' => ['label' => 'Password', 'secret' => true],
            ],
            self::Nagad => [
                'merchant_id' => ['label' => 'Merchant ID', 'secret' => false],
                'merchant_number' => ['label' => 'Merchant Number', 'secret' => false],
                'public_key' => ['label' => 'Nagad Public Key', 'secret' => false],
                'private_key' => ['label' => 'Merchant Private Key', 'secret' => true],
            ],
            self::Sslcommerz => [
                'store_id' => ['label' => 'Store ID', 'secret' => false],
                'store_password' => ['label' => 'Store Password', 'secret' => true],
            ],
        };
    }

    public function hasSandbox(): bool
    {
        return $this !== self::CashOnDelivery;
    }
}
