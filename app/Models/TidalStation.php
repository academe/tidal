<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class TidalStation extends Model
{
    /**
     * The primary key is a string, not an auto-incrementing integer
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     * Set to false for string IDs.
     */
    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'country',
        'longitude',
        'latitude',
        'continuous_heights_available',
        'footnote',
        'raw_data',
    ];

    protected $casts = [
        'continuous_heights_available' => 'boolean',
        'raw_data' => 'array',
        'longitude' => 'double',
        'latitude' => 'double',
    ];

    /**
     * Get location as an array
     *
     * @return array|null [lon, lat]
     */
    public function getLocation(): ?array
    {
        if (is_null($this->longitude) || is_null($this->latitude)) {
            return null;
        }

        return [$this->longitude, $this->latitude];
    }

    /**
     * Set location from an array
     *
     * @param array|null $coordinates [lon, lat]
     * @return void
     */
    public function setLocation(?array $coordinates): void
    {
        if (is_array($coordinates) && count($coordinates) === 2) {
            $this->longitude = $coordinates[0];
            $this->latitude = $coordinates[1];
        } else {
            $this->longitude = null;
            $this->latitude = null;
        }
    }

    /**
     * Get the tidal events for this station
     */
    public function tidalEvents()
    {
        return $this->hasMany(TidalEvent::class, 'station_id', 'id');
    }

    /**
     * Get the fetch record for this station
     */
    public function fetchRecord()
    {
        return $this->hasOne(TidalStationFetch::class, 'station_id', 'id');
    }
}
