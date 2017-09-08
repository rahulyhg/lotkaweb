<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model as Model;

class Shirt extends Model
{
  protected $table = 'shirts';
  
  protected $fillable = [
    'type',
    'type_class',
    'size',
    'available',
  ];
}
