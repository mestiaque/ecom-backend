<?php

namespace ME\Ecom\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use ME\Traits\HasMedia;

class Brand extends Model
{
    use HasMedia;

    protected $table = 'ecom_brands';

    protected $fillable = ['name', 'slug', 'description', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    /**
     * Logo in me_media (metheme).
     */
    protected function mediaCollections(): array
    {
        return ['logo' => ['single' => true, 'mimes' => 'jpg,jpeg,png,webp,gif,svg', 'max_kb' => 2048, 'conversions' => ['thumb' => 300]]];
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->mediaUrl('logo', 'thumb');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
