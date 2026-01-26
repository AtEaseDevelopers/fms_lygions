<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DriverHoliday extends Model
{
use HasFactory;

    protected $fillable = [
        'driver_id',
        'start_date',
        'end_date',
        'remarks',
    ];

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }}
