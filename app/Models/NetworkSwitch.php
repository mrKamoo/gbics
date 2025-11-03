<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class NetworkSwitch extends Model
{
    protected $table = 'switches';

    protected $fillable = [
        'switch_model_id',
        'bay_id',
        'serial_number',
        'asset_tag',
        'barcode',
        'hostname',
        'status',
        'purchase_date',
        'warranty_end',
        'notes',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'warranty_end' => 'date',
    ];

    /**
     * Get the switch model.
     */
    public function switchModel(): BelongsTo
    {
        return $this->belongsTo(SwitchModel::class);
    }

    /**
     * Get the bay where this switch is located.
     */
    public function bay(): BelongsTo
    {
        return $this->belongsTo(Bay::class);
    }

    /**
     * Get all assignments for this switch.
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class, 'switch_id');
    }

    /**
     * Get active assignments only.
     */
    public function activeAssignments(): HasMany
    {
        return $this->hasMany(Assignment::class, 'switch_id')
            ->whereNull('unassigned_at');
    }

    /**
     * Get all stock movements for this switch.
     */
    public function stockMovements(): MorphMany
    {
        return $this->morphMany(StockMovement::class, 'movable');
    }

    /**
     * Get all alerts related to this switch.
     */
    public function alerts(): MorphMany
    {
        return $this->morphMany(Alert::class, 'related');
    }
}
