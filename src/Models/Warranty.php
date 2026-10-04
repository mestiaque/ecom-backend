<?php

namespace ME\Ecom\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Warranty master data. Entered as duration + unit (6 month), kept in days for calculations.
 */
class Warranty extends Model
{
    /** Days per unit used for the automatic calculation. */
    public const UNITS = ['day' => 1, 'month' => 30, 'year' => 365];

    protected $table = 'ecom_warranties';

    protected $fillable = ['name', 'duration', 'duration_unit', 'days', 'description', 'is_active'];

    protected $casts = ['duration' => 'integer', 'days' => 'integer', 'is_active' => 'boolean'];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public static function daysFor(int $duration, string $unit): int
    {
        return $duration * (self::UNITS[$unit] ?? 1);
    }

    /**
     * "6 Months", "1 Year", "7 Days"
     */
    public function getPeriodAttribute(): string
    {
        return $this->duration.' '.ucfirst($this->duration_unit).($this->duration === 1 ? '' : 's');
    }
}
