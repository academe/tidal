<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TidalStationFetch extends Model
{
    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'station_id';

    /**
     * The data type of the primary key.
     * Set to string for non-integer IDs.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     * Set to false for string IDs.
     *
     * @var bool
     */
    public $incrementing = false;

    protected $fillable = [
        'tidal_station_id',
        'last_fetch_at',
        'fetch_error',
        'error_message',
    ];

    protected $casts = [
        'last_fetch_at' => 'datetime',
        'fetch_error' => 'boolean',
    ];

    /**
     * Get the station that this fetch record belongs to
     */
    public function station(): BelongsTo
    {
        return $this->belongsTo(TidalStation::class, 'station_id', 'id');
    }
}