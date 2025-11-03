<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockMovement extends Model
{
    protected $fillable = [
        'movable_type',
        'movable_id',
        'movement_type',
        'from_site_id',
        'to_site_id',
        'quantity',
        'reason',
        'performed_by',
        'performed_at',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'performed_at' => 'datetime',
    ];

    /**
     * Get the movable model (Gbic, PatchCord, or NetworkSwitch).
     */
    public function movable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the site this movement is from.
     */
    public function fromSite(): BelongsTo
    {
        return $this->belongsTo(Site::class, 'from_site_id');
    }

    /**
     * Get the site this movement is to.
     */
    public function toSite(): BelongsTo
    {
        return $this->belongsTo(Site::class, 'to_site_id');
    }

    /**
     * Get the user who performed this movement.
     */
    public function performedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
