<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'nickname',
        'type',
        'account_number',
        'phone',
        'billing_address',
        'draft_customer_id',
        'email',
        'company_reg_no_new',
        'company_reg_no_old',
        'website',
        'billing_phone',
        'remark',
        'consignor_currency',
        'consignee_currency',
        'city',
        'post_code',
        'state',
        'country',
        'tin',
        'service_tax_no',
        'contact_person',
        'term',
        'lygion_id'
    ];

    public function locations()
    {
        return $this->hasMany(CustomerLocation::class);
    }

    public function draft()
{
    return $this->belongsTo(DraftCustomer::class, 'draft_customer_id');
}
}
