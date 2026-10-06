<?php

namespace ME\Ecom\Models;

use Illuminate\Database\Eloquent\Model;
use ME\Traits\HasMedia;

class Banner extends Model
{
    use HasMedia;

    protected $table = 'ecom_banners';

    protected $fillable = ['title', 'subtitle', 'link', 'button_text', 'position', 'sort_order', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public const POSITIONS = ['slider' => 'Homepage Slider', 'promo' => 'Promo Banner'];

    /**
     * Banner picture in me_media (metheme).
     */
    protected function mediaCollections(): array
    {
        return ['image' => ['single' => true, 'mimes' => 'jpg,jpeg,png,webp,gif,svg', 'max_kb' => 4096, 'conversions' => ['thumb' => 600]]];
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->mediaUrl('image');
    }
}
