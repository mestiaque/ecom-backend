<?php

namespace ME\Ecom\Services\Couriers;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use ME\Ecom\Models\Order;

class CourierManager
{
    /** @var array<int, class-string<CourierDriver>> */
    private array $drivers = [
        SteadfastCourier::class,
        PathaoCourier::class,
        RedxCourier::class,
    ];

    /**
     * @return array<string, CourierDriver>
     */
    public function all(): array
    {
        $drivers = [];

        foreach ($this->drivers as $class) {
            $driver = app($class);
            $drivers[$driver->key()] = $driver;
        }

        return $drivers;
    }

    public function driver(string $key): CourierDriver
    {
        return $this->all()[$key] ?? throw new InvalidArgumentException("Unknown courier [{$key}].");
    }

    /**
     * Book the parcel through the courier API and save the tracking id on the order.
     *
     * @param  array<string, mixed>  $options
     *
     * @throws CourierException
     */
    public function send(Order $order, string $courier, array $options = [], ?int $userId = null): CourierResult
    {
        $driver = $this->driver($courier);

        if (! $driver->isConfigured()) {
            throw new CourierException("{$driver->label()} is not configured. Add its credentials on the Couriers settings page.");
        }

        $result = $driver->createParcel($order->loadMissing('items'), $options);
        $this->assign($order, $courier, $result->trackingId, $result->consignmentId, "Sent to {$driver->label()} (tracking {$result->trackingId})", $userId);

        return $result;
    }

    /**
     * Save courier + tracking id given by hand (parcel booked outside the system).
     */
    public function assign(Order $order, string $courier, string $trackingId, ?string $consignmentId = null, ?string $note = null, ?int $userId = null): void
    {
        DB::transaction(function () use ($order, $courier, $trackingId, $consignmentId, $note, $userId) {
            $order->update([
                'courier' => $courier,
                'tracking_id' => $trackingId,
                'consignment_id' => $consignmentId,
                'sent_to_courier_at' => now(),
            ]);

            $order->notes()->create([
                'note' => $note ?? 'Courier: '.$this->labelFor($courier).", tracking {$trackingId}",
                'user_id' => $userId,
            ]);
        });
    }

    public function labelFor(?string $courier): string
    {
        return config("ecom.couriers.{$courier}.label") ?? ucfirst((string) $courier);
    }
}
