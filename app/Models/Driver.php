<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    use HasFactory;

    protected $connection = 'snl';
    protected $table = 'drivers';

    protected $fillable = [
        'name',
        'group',
        'id_number',
        'phone_number_mas',
        'phone_number_sg',
        'default_lorry_id',
        'note',
        'is_outsider',
        // Virtual field names (mapped via mutators)
        'phone_my',
        'phone_sg',
        'truck',
        'outsider',
    ];

    // Accessor: phone_my
    public function getPhoneMyAttribute()
    {
        return $this->attributes['phone_number_mas'] ?? null;
    }

    // Mutator: phone_my
    public function setPhoneMyAttribute($value)
    {
        $this->attributes['phone_number_mas'] = $value;
    }

    // Accessor: phone_sg
    public function getPhoneSgAttribute()
    {
        return $this->attributes['phone_number_sg'] ?? null;
    }

    // Mutator: phone_sg
    public function setPhoneSgAttribute($value)
    {
        $this->attributes['phone_number_sg'] = $value;
    }

    // Accessor: truck (resolves default_lorry_id to lorry number)
    public function getTruckAttribute()
    {
        if (!empty($this->attributes['default_lorry_id'])) {
            return $this->defaultLorry?->number;
        }
        return null;
    }

    // Mutator: truck (resolves lorry number to default_lorry_id)
    public function setTruckAttribute($value)
    {
        if ($value) {
            $lorry = Truck::where('number', $value)->first();
            $this->attributes['default_lorry_id'] = $lorry?->id;
        } else {
            $this->attributes['default_lorry_id'] = null;
        }
    }

    // Accessor: outsider
    public function getOutsiderAttribute()
    {
        return $this->attributes['is_outsider'] ?? false;
    }

    // Mutator: outsider
    public function setOutsiderAttribute($value)
    {
        $this->attributes['is_outsider'] = $value;
    }

    public function defaultLorry()
    {
        return $this->belongsTo(Truck::class, 'default_lorry_id');
    }
}
