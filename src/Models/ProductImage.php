<?php

namespace ME\Ecom\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use ME\Ecom\Jobs\GenerateProductThumbnail;

class ProductImage extends Model
{
    protected $table = 'ecom_product_images';

    protected $fillable = ['product_id', 'path', 'thumbnail', 'sort_order'];

    protected static function booted(): void
    {
        // Every new image gets a small thumbnail made in the background
        static::created(fn (ProductImage $image) => GenerateProductThumbnail::dispatchFor($image));

        static::deleted(function (ProductImage $image): void {
            if ($image->thumbnail) {
                Storage::disk('public')->delete($image->thumbnail);
            }
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getUrlAttribute(): ?string
    {
        return ecom_image($this->path);
    }

    /**
     * Small version for lists; the full image until the thumbnail job has run.
     */
    public function getThumbUrlAttribute(): ?string
    {
        return ecom_image($this->thumbnail ?? $this->path);
    }
}
