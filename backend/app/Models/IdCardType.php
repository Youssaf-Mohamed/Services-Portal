<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IdCardType extends Model
{
    protected $fillable = [
        'code',
        'name_ar',
        'name_en',
        'description_ar',
        'description_en',
        'fee',
        'requires_photo',
        'requires_description',
        'active',
        'sort_order',
    ];

    protected $casts = [
        'fee' => 'decimal:2',
        'requires_photo' => 'boolean',
        'requires_description' => 'boolean',
        'active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get all requests of this type.
     */
    public function requests(): HasMany
    {
        return $this->hasMany(IdCardRequest::class, 'type_id');
    }

    /**
     * Scope to get only active types.
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope to order by sort_order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Find a type by its code.
     */
    public static function findByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }

    /**
     * Find an active type by its code.
     */
    public static function findActiveByCode(string $code): ?self
    {
        return static::active()->where('code', $code)->first();
    }
}
