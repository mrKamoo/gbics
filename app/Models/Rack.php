<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rack extends Model
{
    protected $fillable = [
        'site_id',
        'name',
        'location',
        'units',
    ];

    protected $casts = [
        'units' => 'integer',
    ];

    /**
     * Get the site that owns this rack.
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * Get all bays in this rack.
     */
    public function bays(): HasMany
    {
        return $this->hasMany(Bay::class);
    }
}
