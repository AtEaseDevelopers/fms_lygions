<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Truck extends Model
{
    use HasFactory;

    protected $connection = 'snl';
    protected $table = 'lorries';

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
        'team',
        'size',
        'is_outsider',
    ];

    protected static $chassisTypeMap = [
        1 => 'curtain',
        2 => 'open',
        3 => 'box',
        4 => 'tailgate',
    ];

    protected static $chassisTypeReverseMap = [
        'curtain' => 1,
        'open' => 2,
        'box' => 3,
        'tailgate' => 4,
    ];

    protected static $sizeMap = [
        1 => 'Any',
        2 => 'Small',
    ];

    protected static $sizeReverseMap = [
        'Any' => 1,
        'Small' => 2,
    ];

    public static function chassisTypeMap(): array
    {
        return static::$chassisTypeMap;
    }

    public function getChassisTypeAttribute($value)
    {
        if (is_numeric($value)) {
            return static::$chassisTypeMap[(int) $value] ?? $value;
        }
        return $value;
    }

    public function setChassisTypeAttribute($value)
    {
        if (isset(static::$chassisTypeReverseMap[$value])) {
            $this->attributes['chassis_type'] = static::$chassisTypeReverseMap[$value];
        } else {
            $this->attributes['chassis_type'] = $value;
        }
    }

    public function getSizeAttribute($value)
    {
        if (is_numeric($value)) {
            return static::$sizeMap[(int) $value] ?? $value;
        }
        return $value;
    }

    public function setSizeAttribute($value)
    {
        if (isset(static::$sizeReverseMap[$value])) {
            $this->attributes['size'] = static::$sizeReverseMap[$value];
        } else {
            $this->attributes['size'] = $value;
        }
    }

}
