<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class PatchCord extends Model
{
    protected $fillable = [
        'fs_com_product_id',
        'serial_number',
        'barcode',
        'length',
        'connector_type_a',
        'connector_type_b',
        'fiber_type',
        'status',
        'purchase_date',
        'notes',
    ];

    protected $casts = [
        'length' => 'decimal:2',
        'purchase_date' => 'date',
    ];

    /**
     * Get the FS.com product.
     */
    public function fsComProduct(): BelongsTo
    {
        return $this->belongsTo(FsComProduct::class);
    }

    /**
     * Get all assignments for this patch cord.
     */
    public function assignments(): MorphMany
    {
        return $this->morphMany(Assignment::class, 'assignable');
    }

    /**
     * Get the current active assignment.
     */
    public function currentAssignment(): MorphOne
    {
        return $this->morphOne(Assignment::class, 'assignable')
            ->whereNull('unassigned_at');
    }

    /**
     * Get all stock movements for this patch cord.
     */
    public function stockMovements(): MorphMany
    {
        return $this->morphMany(StockMovement::class, 'movable');
    }

    /**
     * Get all alerts related to this patch cord.
     */
    public function alerts(): MorphMany
    {
        return $this->morphMany(Alert::class, 'related');
    }
}
