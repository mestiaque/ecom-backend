<?php

namespace ME\Ecom\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    public const GENERAL = 'General';

    protected $table = 'ecom_faqs';

    protected $fillable = ['category', 'question', 'answer', 'sort_order', 'is_active'];

    protected $casts = ['sort_order' => 'integer', 'is_active' => 'boolean'];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function getCategoryNameAttribute(): string
    {
        return $this->category ?: self::GENERAL;
    }

    /**
     * Categories already used, for the category suggestions in the form.
     *
     * @return array<int, string>
     */
    public static function categories(): array
    {
        return self::whereNotNull('category')->distinct()->orderBy('category')->pluck('category')->all();
    }
}
