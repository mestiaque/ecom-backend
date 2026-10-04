<?php

namespace ME\Ecom\Models;

use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    protected $table = 'ecom_banners';

    protected $fillable = ['title', 'subtitle', 'image', 'link', 'button_text', 'position', 'sort_order', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public const POSITIONS = ['slider' => 'Homepage Slider', 'promo' => 'Promo Banner'];
}
