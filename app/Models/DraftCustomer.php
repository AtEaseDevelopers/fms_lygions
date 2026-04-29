<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DraftCustomer extends Model
{
    use HasFactory;

    const TERM_CASH = 1;
    const TERM_30_DAYS = 2;
    const TERM_45_DAYS = 3;
    const TERM_60_DAYS = 4;

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

    public function convertTermToWord($term): ?string
    {
        if ($term === null || $term === '') {
            return null;
        }
        switch ((int) $term) {
            case self::TERM_CASH:    return 'Cash';
            case self::TERM_30_DAYS: return '30 Days';
            case self::TERM_45_DAYS: return '45 Days';
            case self::TERM_60_DAYS: return '60 Days';
        }
        return null;
    }
}
