<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Consignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'load_date',
        'consignment_no',
        'consignor',
        'pick_point',
        'consignee',
        'drop_point',
        // 'truck_type',
        'truck_number',
        'remarks',
        'billing_remark',
        'status',
        // 'pick_truck',
        // 'drop_truck',
        'pick_address',
        'drop_address',
        'pick_time',
        'quantity',
        'unit',
        'pre_pick',
        'express_mode',
        'self_delivery',
        'driver',
        'pick_truck_size',
        'drop_truck_size',
        'pick_truck_type',
        'drop_truck_type',
    ];
    public function driverInfo()
    {
        return $this->belongsTo(Driver::class, 'driver', 'id');
    }

}
