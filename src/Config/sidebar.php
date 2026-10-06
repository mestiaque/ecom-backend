<?php

/*
| Ecom sidebar — merged into config('sidebar') and sorted by "sl".
| A group is hidden when the user can see none of its children.
*/
return [
    [
        'title' => 'Dashboard',
        'icon' => 'fas fa-tachometer-alt',
        'route' => 'ecom.dashboard',
        'for_active' => 'ecom.dashboard',
        'icon_color' => 'icc-38',
        'permit' => 'ecom.dashboard',
        'sl' => 1,
    ],
    [
        'title' => 'Orders & Sales',
        'icon' => 'fas fa-shopping-cart',
        'icon_color' => 'icc-81',
        'sl' => 3,
        'children' => [
            ['title' => 'Orders',       'icon' => 'fas fa-receipt',         'route' => 'ecom.orders.index',       'for_active' => 'ecom.orders.',       'permit' => 'ecom_order.view',    'icon_color' => 'icc-81'],
            ['title' => 'Transactions', 'icon' => 'fas fa-money-check-alt', 'route' => 'ecom.transactions.index', 'for_active' => 'ecom.transactions.', 'permit' => 'ecom_payment.view',  'icon_color' => 'icc-38'],
            ['title' => 'Customers',    'icon' => 'fas fa-user-friends',    'route' => 'ecom.customers.index',    'for_active' => 'ecom.customers.',    'permit' => 'ecom_customer.view', 'icon_color' => 'icc-67'],
        ],
    ],
    [
        'title' => 'Catalog',
        'icon' => 'fas fa-boxes',
        'icon_color' => 'icc-67',
        'sl' => 4,
        'children' => [
            ['title' => 'Products',   'icon' => 'fas fa-box',     'route' => 'ecom.products.index',   'for_active' => 'ecom.products.',   'permit' => 'ecom_product.view',  'icon_color' => 'icc-67'],
            ['title' => 'Categories', 'icon' => 'fas fa-sitemap', 'route' => 'ecom.categories.index', 'for_active' => 'ecom.categories.', 'permit' => 'ecom_category.view', 'icon_color' => 'icc-38'],
            ['title' => 'Brands',     'icon' => 'fas fa-tags',    'route' => 'ecom.brands.index',     'for_active' => 'ecom.brands.',     'permit' => 'ecom_brand.view',    'icon_color' => 'icc-81'],
            ['title' => 'Attributes', 'icon' => 'fas fa-palette', 'route' => 'ecom.attributes.index', 'for_active' => 'ecom.attributes.', 'permit' => 'ecom_attribute.view', 'icon_color' => 'icc-38'],
            ['title' => 'Warranties', 'icon' => 'fas fa-shield-alt', 'route' => 'ecom.warranties.index', 'for_active' => 'ecom.warranties.', 'permit' => 'ecom_warranty.view', 'icon_color' => 'icc-81'],
            ['title' => 'Reviews',    'icon' => 'fas fa-star',    'route' => 'ecom.reviews.index',    'for_active' => 'ecom.reviews.',    'permit' => 'ecom_review.view',   'icon_color' => 'icc-67'],
        ],
    ],
    [
        'title' => 'Marketing',
        'icon' => 'fas fa-bullhorn',
        'icon_color' => 'icc-38',
        'sl' => 5,
        'children' => [
            ['title' => 'Coupons',               'icon' => 'fas fa-ticket-alt', 'route' => 'ecom.coupons.index',   'for_active' => 'ecom.coupons.',   'permit' => 'ecom_coupon.view',   'icon_color' => 'icc-38'],
            ['title' => 'Flash Sale / Campaign', 'icon' => 'fas fa-bolt',       'route' => 'ecom.campaigns.index', 'for_active' => 'ecom.campaigns.', 'permit' => 'ecom_campaign.view', 'icon_color' => 'icc-81'],
        ],
    ],
    [
        'title' => 'Website',
        'icon' => 'fas fa-globe',
        'icon_color' => 'icc-81',
        'sl' => 6,
        'children' => [
            ['title' => 'Banners / Slider', 'icon' => 'fas fa-images',      'route' => 'ecom.banners.index',  'for_active' => 'ecom.banners.',       'permit' => 'ecom_content.banner', 'icon_color' => 'icc-81'],
            ['title' => 'Pages',            'icon' => 'fas fa-file-alt',    'route' => 'ecom.pages.index',    'for_active' => 'ecom.pages.',         'permit' => 'ecom_content.page',   'icon_color' => 'icc-67'],
            ['title' => 'FAQ',              'icon' => 'fas fa-question-circle', 'route' => 'ecom.faqs.index', 'for_active' => 'ecom.faqs.',          'permit' => 'ecom_content.faq',    'icon_color' => 'icc-52'],
            ['title' => 'Store Info',       'icon' => 'fas fa-info-circle', 'route' => 'ecom.settings.store', 'for_active' => 'ecom.settings.store', 'permit' => 'ecom_setting.store',  'icon_color' => 'icc-38'],
        ],
    ],
    [
        'title' => 'Shop Settings',
        'icon' => 'fas fa-cogs',
        'icon_color' => 'icc-67',
        'sl' => 7,
        'children' => [
            ['title' => 'Shipping',        'icon' => 'fas fa-truck',         'route' => 'ecom.shipping.index',   'for_active' => 'ecom.shipping.',         'permit' => 'ecom_setting.shipping', 'icon_color' => 'icc-67'],
            ['title' => 'Payment Methods', 'icon' => 'fas fa-credit-card',   'route' => 'ecom.settings.payment', 'for_active' => 'ecom.settings.payment', 'permit' => 'ecom_setting.payment',  'icon_color' => 'icc-81'],
            ['title' => 'Couriers',        'icon' => 'fas fa-shipping-fast', 'route' => 'ecom.settings.courier', 'for_active' => 'ecom.settings.courier', 'permit' => 'ecom_setting.courier',  'icon_color' => 'icc-38'],
        ],
    ],
    [
        'title' => 'Shop Reports',
        'icon' => 'fas fa-chart-line',
        'icon_color' => 'icc-38',
        'sl' => 8,
        'children' => [
            ['title' => 'Sales Report',    'icon' => 'fas fa-chart-bar', 'route' => 'ecom.reports.sales',     'for_active' => 'ecom.reports.sales',     'permit' => 'ecom_report.sales',    'icon_color' => 'icc-38'],
            ['title' => 'Product Report',  'icon' => 'fas fa-chart-pie', 'route' => 'ecom.reports.products',  'for_active' => 'ecom.reports.products',  'permit' => 'ecom_report.product',  'icon_color' => 'icc-81'],
            ['title' => 'Customer Report', 'icon' => 'fas fa-users',     'route' => 'ecom.reports.customers', 'for_active' => 'ecom.reports.customers', 'permit' => 'ecom_report.customer', 'icon_color' => 'icc-67'],
        ],
    ],
];
