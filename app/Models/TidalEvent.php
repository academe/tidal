<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TidalEvent extends Model
{
    protected $fillable = [
        'station_id',
        'event_type',
        'event_datetime',
        'height',
        'is_approximate_time',
        'is_approximate_height',
        'filtered',
        'raw_data',
    ];

    protected $casts = [
        'event_datetime' => 'datetime',
        'height' => 'double',
        'is_approximate_time' => 'boolean',
        'is_approximate_height' => 'boolean',
        'filtered' => 'boolean',
        'raw_data' => 'array',
        // Important: do NOT cast station_id to integer
    ];

    /**
     * Get the station that this event belongs to
     */
    public function station(): BelongsTo
    {
        return $this->belongsTo(TidalStation::class, 'station_id', 'id');
    }
}
