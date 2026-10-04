<?php

/*
| Format: module => [title, actions]; each key becomes "module.action" (metheme doc §6).
| Every permission checked in this package must be declared here.
*/
return [
    'ecom' => [
        'title' => 'Shop Dashboard',
        'actions' => 'dashboard',
    ],
    'ecom_product' => [
        'title' => 'Products',
        'actions' => 'view,create,edit,delete,import,export',
    ],
    'ecom_category' => [
        'title' => 'Categories',
        'actions' => 'view,create,edit,delete',
    ],
    'ecom_brand' => [
        'title' => 'Brands',
        'actions' => 'view,create,edit,delete',
    ],
    'ecom_attribute' => [
        'title' => 'Variant Attributes',
        'actions' => 'view,create,edit,delete',
    ],
    'ecom_warranty' => [
        'title' => 'Warranties',
        'actions' => 'view,create,edit,delete',
    ],
    'ecom_order' => [
        'title' => 'Orders',
        'actions' => 'view,status,note,courier,invoice,export',
    ],
    'ecom_payment' => [
        'title' => 'Payments',
        'actions' => 'view,record,refund',
    ],
    'ecom_customer' => [
        'title' => 'Customers',
        'actions' => 'view,block',
    ],
    'ecom_coupon' => [
        'title' => 'Coupons',
        'actions' => 'view,create,edit,delete',
    ],
    'ecom_campaign' => [
        'title' => 'Campaigns / Flash Sale',
        'actions' => 'view,create,edit,delete',
    ],
    'ecom_review' => [
        'title' => 'Reviews',
        'actions' => 'view,approve,delete',
    ],
    'ecom_content' => [
        'title' => 'Website Content',
        'actions' => 'banner,page,faq',
    ],
    'ecom_setting' => [
        'title' => 'Shop Settings',
        'actions' => 'store,shipping,payment,courier',
    ],
    'ecom_report' => [
        'title' => 'Shop Reports',
        'actions' => 'sales,product,customer',
    ],
];
