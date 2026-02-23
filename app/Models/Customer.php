<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'snl';
    protected $table = 'customers';

    protected $fillable = [
        'name',
        'nickname',
        'account_number',
        'phone',
        'billing_address',
        'email',
        'company_registration_number',
        'company_registration_number_old',
        'website',
        'billing_phone',
        'remark',
        'consignor_default_currency',
        'consignee_default_currency',
        'cityname',
        'postcode',
        'state',
        'country',
        'income_tax',
        'service_tax',
        'contact_person',
        'term',
        'labels',
        // Virtual field names (mapped via mutators to SNL columns)
        'company_reg_no_new',
        'company_reg_no_old',
        'consignor_currency',
        'consignee_currency',
        'city',
        'post_code',
        'tin',
        'service_tax_no',
        'type',
    ];

    protected $casts = [
        'labels' => 'array',
    ];

    // Accessors for backward compatibility with views

    public function getCompanyRegNoNewAttribute()
    {
        return $this->attributes['company_registration_number'] ?? null;
    }

    public function setCompanyRegNoNewAttribute($value)
    {
        $this->attributes['company_registration_number'] = $value;
    }

    public function getCompanyRegNoOldAttribute()
    {
        return $this->attributes['company_registration_number_old'] ?? null;
    }

    public function setCompanyRegNoOldAttribute($value)
    {
        $this->attributes['company_registration_number_old'] = $value;
    }

    public function getConsignorCurrencyAttribute()
    {
        return $this->attributes['consignor_default_currency'] ?? null;
    }

    public function setConsignorCurrencyAttribute($value)
    {
        $this->attributes['consignor_default_currency'] = $value;
    }

    public function getConsigneeCurrencyAttribute()
    {
        return $this->attributes['consignee_default_currency'] ?? null;
    }

    public function setConsigneeCurrencyAttribute($value)
    {
        $this->attributes['consignee_default_currency'] = $value;
    }

    public function getCityAttribute()
    {
        return $this->attributes['cityname'] ?? null;
    }

    public function setCityAttribute($value)
    {
        $this->attributes['cityname'] = $value;
    }

    public function getPostCodeAttribute()
    {
        return $this->attributes['postcode'] ?? null;
    }

    public function setPostCodeAttribute($value)
    {
        $this->attributes['postcode'] = $value;
    }

    public function getTinAttribute()
    {
        return $this->attributes['income_tax'] ?? null;
    }

    public function setTinAttribute($value)
    {
        $this->attributes['income_tax'] = $value;
    }

    public function getServiceTaxNoAttribute()
    {
        return $this->attributes['service_tax'] ?? null;
    }

    public function setServiceTaxNoAttribute($value)
    {
        $this->attributes['service_tax'] = $value;
    }

    public function getTypeAttribute()
    {
        $labels = $this->labels;
        if (is_array($labels) && in_array(2, $labels)) {
            return 'Consignee';
        }
        return 'Consignor';
    }

    public function setTypeAttribute($value)
    {
        // Write as raw array; the 'labels' cast will handle JSON encoding on save
        if ($value === 'Consignee') {
            $this->labels = [2];
        } else {
            $this->labels = [1];
        }
    }

    public function scopeOfType($query, $type)
    {
        if ($type === 'Consignee') {
            return $query->whereJsonContains('labels', 2);
        }
        return $query->whereJsonDoesntContain('labels', 2);
    }

    public function locations()
    {
        return $this->hasMany(CustomerLocation::class);
    }

    public function draft()
    {
        return $this->belongsTo(DraftCustomer::class, 'draft_customer_id');
    }
}
