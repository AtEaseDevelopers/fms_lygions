<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Truck extends Model
{
    use HasFactory;

    protected $fillable = [
        'number',
        'group',
        'tonnage',
        'floor_space',
        'chassis_type',
        'next_inspection',
        'next_tyre',
        'next_permit',
        'next_extinguisher',
        'next_roadtax',
        'next_insurance',
        'next_others',
        'lygions_id',
        'team',
        'size'
    ];
}
