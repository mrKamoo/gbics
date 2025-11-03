<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bay extends Model
{
    protected $fillable = [
        'rack_id',
        'position',
        'name',
    ];

    protected $casts = [
        'position' => 'integer',
    ];

    /**
     * Get the rack that owns this bay.
     */
    public function rack(): BelongsTo
    {
        return $this->belongsTo(Rack::class);
    }

    /**
     * Get all switches in this bay.
     */
    public function switches(): HasMany
    {
        return $this->hasMany(NetworkSwitch::class);
    }
}
