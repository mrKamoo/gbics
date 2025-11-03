<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SwitchModel extends Model
{
    protected $fillable = [
        'manufacturer',
        'model',
        'port_count',
        'port_types',
        'description',
    ];

    protected $casts = [
        'port_count' => 'integer',
        'port_types' => 'array',
    ];

    /**
     * Get all switches of this model.
     */
    public function switches(): HasMany
    {
        return $this->hasMany(NetworkSwitch::class);
    }
}
