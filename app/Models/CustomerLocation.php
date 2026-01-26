<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CustomerLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'state',
        'address',
        'pic',
        'phone',
        'type',
        'truck_size',
        'truck_type',
        'load_type',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
