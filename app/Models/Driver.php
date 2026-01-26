<?php
// app/Models/Driver.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'group', 'id_number', 'phone_my', 'phone_sg', 'truck', 'note','lygion_id'
    ];

}
