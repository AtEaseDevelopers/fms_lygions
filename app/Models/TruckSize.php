<?php
// app/Models/TruckSize.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TruckSize extends Model
{
    use HasFactory;

    protected $fillable = ['size', 'sqft'];
}

