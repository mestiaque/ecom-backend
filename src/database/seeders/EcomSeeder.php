<?php

namespace ME\Ecom\database\seeders;

use Illuminate\Database\Seeder;
use ME\Ecom\Models\Page;
use ME\Ecom\Models\ShippingDiscount;
use ME\Ecom\Models\ShippingZone;
use ME\Ecom\Models\Warranty;
use ME\Ecom\Support\EcomSettings;

/**
 * Default shop data: delivery zones, policy pages and basic settings. Safe to run more than once.
 *
 * php artisan db:seed --class="ME\Ecom\database\seeders\EcomSeeder"
 */
class EcomSeeder extends Seeder
{
    public function run(EcomSettings $settings): void
    {
        foreach ([['Inside Dhaka', 60, '1-2 days', 1], ['Dhaka Suburbs', 100, '2-3 days', 2], ['Outside Dhaka', 120, '3-5 days', 3]] as [$name, $charge, $time, $sort]) {
            ShippingZone::firstOrCreate(['name' => $name], ['charge' => $charge, 'delivery_time' => $time, 'sort_order' => $sort]);
        }

        $warranties = [
            ['7 Days Replacement', 7, 'day', 'Defective or wrong product replaced within 7 days of delivery.'],
            ['3 Months Service Warranty', 3, 'month', 'Free service for manufacturing defects.'],
            ['6 Months Warranty', 6, 'month', 'Covers manufacturing defects. Physical and liquid damage not covered.'],
            ['1 Year Official Warranty', 1, 'year', 'Official brand warranty. Keep the invoice for claims.'],
            ['2 Years Brand Warranty', 2, 'year', 'Brand warranty through authorised service centres.'],
        ];

        foreach ($warranties as [$name, $duration, $unit, $description]) {
            Warranty::firstOrCreate(['name' => $name], [
                'duration' => $duration,
                'duration_unit' => $unit,
                'days' => Warranty::daysFor($duration, $unit),
                'description' => $description,
            ]);
        }

        $pages = [
            'about-us' => ['About Us', '<p>Tell your customers who you are and what you sell.</p>'],
            'privacy-policy' => ['Privacy Policy', '<p>Explain what customer data you collect and how you use it.</p>'],
            'return-policy' => ['Return Policy', '<p>Products can be returned within 7 days of delivery if unused and in original packaging.</p>'],
            'terms-and-conditions' => ['Terms & Conditions', '<p>Terms of using this website and buying from it.</p>'],
        ];

        foreach ($pages as $slug => [$title, $content]) {
            Page::firstOrCreate(['slug' => $slug], ['title' => $title, 'content' => $content]);
        }

        $defaults = [
            'payment_cod_enabled' => '1',
            'low_stock_threshold' => '5',
            'order_prefix' => 'ORD-',
        ];

        $settings->set(array_filter($defaults, fn ($key) => $settings->get($key) === null, ARRAY_FILTER_USE_KEY));

        if (! ShippingDiscount::exists()) {
            ShippingDiscount::create(['min_order_amount' => 2000, 'type' => 'free']);
        }
    }
}
