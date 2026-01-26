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
    ];

    public function truck()
    {
        return $this->belongsTo(Truck::class);
    }

   }


