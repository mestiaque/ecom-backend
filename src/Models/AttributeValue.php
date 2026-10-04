<?php

namespace ME\Ecom\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AttributeValue extends Model
{
    protected $table = 'ecom_attribute_values';

    protected $fillable = ['attribute_id', 'value', 'color_code', 'sort_order'];

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }

    public function variants(): BelongsToMany
    {
        return $this->belongsToMany(ProductVariant::class, 'ecom_product_variant_values', 'attribute_value_id', 'variant_id');
    }
}
