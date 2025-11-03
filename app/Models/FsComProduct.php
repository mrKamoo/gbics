<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FsComProduct extends Model
{
    protected $fillable = [
        'fs_com_id',
        'sku',
        'name',
        'category',
        'description',
        'specifications',
        'price',
        'currency',
        'url',
        'image_url',
        'last_synced_at',
    ];

    protected $casts = [
        'specifications' => 'array',
        'price' => 'decimal:2',
        'last_synced_at' => 'datetime',
    ];

    /**
     * Get all GBICs using this product.
     */
    public function gbics(): HasMany
    {
        return $this->hasMany(Gbic::class);
    }

    /**
     * Get all patch cords using this product.
     */
    public function patchCords(): HasMany
    {
        return $this->hasMany(PatchCord::class);
    }
}
