<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model as Model;

class Team extends Model
{
  protected $table = 'teams';
  
  protected $fillable = [
    'type', 
    'name', 
    'available',
  ];
}
