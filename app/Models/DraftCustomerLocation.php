<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DraftCustomerLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'draft_customer_id',
        'state',
        'address',
        'pic',
        'phone',
        'type',
        'truck_size',
        'truck_type',
        'load_type',
    ];

     public function draftCustomer()
    {
        return $this->belongsTo(DraftCustomer::class, 'draft_customer_id');
    }
}
