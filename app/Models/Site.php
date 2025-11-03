<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Site extends Model
{
    protected $fillable = [
        'name',
        'address',
        'city',
        'country',
        'contact_name',
        'contact_phone',
    ];

    /**
     * Get all racks for this site.
     */
    public function racks(): HasMany
    {
        return $this->hasMany(Rack::class);
    }

    /**
     * Get all stock movements from this site.
     */
    public function stockMovementsFrom(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'from_site_id');
    }

    /**
     * Get all stock movements to this site.
     */
    public function stockMovementsTo(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'to_site_id');
    }
}
