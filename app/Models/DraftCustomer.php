<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DraftCustomer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'nickname',
        'type',
        'account_number',
        'phone',
        'billing_address',
        'migrated',
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
    ];

    public function locations()
    {
    return $this->hasMany(DraftCustomerLocation::class, 'draft_customer_id');
    }
    public function customer()
{
    return $this->hasOne(Customer::class, 'draft_customer_id');
}
}
