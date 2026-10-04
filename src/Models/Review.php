<?php

namespace ME\Ecom\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    protected $table = 'ecom_reviews';

    protected $fillable = ['product_id', 'customer_id', 'name', 'rating', 'comment', 'is_approved'];

    protected $casts = ['rating' => 'integer', 'is_approved' => 'boolean'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
