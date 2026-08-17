<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Availability extends Model
{
use HasFactory;

    protected $fillable = [
        'truck_id',
          'subcon_id',
        'date',
        'location',
        'status',
        'remarks',
    ];

    public function truck()
    {
        return $this->belongsTo(Truck::class);
    }

    /**
     * Colour + label profile for each availability status / special arrangement.
     * Single source of truth used by the calendar cell, the legend and the seeder.
     * ('driver-leave' is handled via DriverHoliday, not stored here.)
     */
    public static function arrangementProfiles(): array
    {
        return [
            'off-day'            => ['label' => 'Off Day',         'color' => '#c3c2c2'],
            'maintenance'        => ['label' => 'Maintenance',     'color' => '#c3c2c2'],
            'holiday'            => ['label' => 'Holiday',         'color' => '#c39bd3'],
            'breakdown'          => ['label' => 'Breakdown',       'color' => '#e59866'],
            'express'            => ['label' => 'Express',         'color' => '#7dcea0'],
            'inspection'         => ['label' => 'Inspection',      'color' => '#7fb3d5'],
            'saturday-loading'   => ['label' => 'Sat Load (KL)',   'color' => '#48c9b0'],
            'saturday-unloading' => ['label' => 'Sat Unload (SG)', 'color' => '#f7dc6f'],
        ];
    }
}


