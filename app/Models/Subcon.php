<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subcon extends Model
{
    use HasFactory;

    protected $fillable = [
        'subcon_name',
        'truck_no',
        'driver_name',
        'group',
        'tonnage',
        'floor_space',
        'chassis_type',
        'phone_my',
        'phone_sg',
        'lygion_id',
        'team',
        'size'
    ];
}
