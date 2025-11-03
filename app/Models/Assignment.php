<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Assignment extends Model
{
    protected $fillable = [
        'assignable_type',
        'assignable_id',
        'switch_id',
        'port_number',
        'assigned_at',
        'unassigned_at',
        'assigned_by',
        'unassigned_by',
        'notes',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'unassigned_at' => 'datetime',
        'port_number' => 'integer',
    ];

    /**
     * Get the assignable model (Gbic or PatchCord).
     */
    public function assignable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the switch this assignment belongs to.
     */
    public function switch(): BelongsTo
    {
        return $this->belongsTo(NetworkSwitch::class, 'switch_id');
    }

    /**
     * Get the user who assigned this.
     */
    public function assignedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /**
     * Get the user who unassigned this.
     */
    public function unassignedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'unassigned_by');
    }

    /**
     * Scope for active assignments only.
     */
    public function scopeActive($query)
    {
        return $query->whereNull('unassigned_at');
    }
}
