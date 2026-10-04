<?php

namespace ME\Ecom\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A variant attribute such as Color, Size, Storage or Material.
 */
class Attribute extends Model
{
    public const TYPES = ['select' => 'Text / Button', 'color' => 'Color swatch'];

    protected $table = 'ecom_attributes';

    protected $fillable = ['name', 'slug', 'type', 'sort_order'];

    public function values(): HasMany
    {
        return $this->hasMany(AttributeValue::class)->orderBy('sort_order')->orderBy('id');
    }

    public function isColor(): bool
    {
        return $this->type === 'color';
    }
}
