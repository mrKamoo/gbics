<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Gbic extends Model
{
    protected $fillable = [
        'fs_com_product_id',
        'serial_number',
        'barcode',
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
     * Get the FS.com product.
     */
    public function fsComProduct(): BelongsTo
    {
        return $this->belongsTo(FsComProduct::class);
    }

    /**
     * Get all assignments for this GBIC.
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
     * Get all stock movements for this GBIC.
     */
    public function stockMovements(): MorphMany
    {
        return $this->morphMany(StockMovement::class, 'movable');
    }

    /**
     * Get all alerts related to this GBIC.
     */
    public function alerts(): MorphMany
    {
        return $this->morphMany(Alert::class, 'related');
    }
}
