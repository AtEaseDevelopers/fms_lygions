<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TemporaryTruck extends Model
{
    use HasFactory;

    protected $table = 'temporary_trucks';

    protected $fillable = [
        'date',
        'location',
        'chassis_type',
        'size',
        'label',
        'subcon_id',
        'floor_space',
    ];

    protected $casts = [
        'date' => 'date',
        'floor_space' => 'decimal:2',
    ];

    public function subcon()
    {
        return $this->belongsTo(Subcon::class);
    }
}
