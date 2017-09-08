<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model as Model;

class Ticket extends Model
{
  protected $table = 'tickets';
  
  protected $fillable = [
    'sku', 
    'price', 
    'description', 
    'statement_descriptor', 
    'available', 
    'visibility',
    'img', 
    'weight', 
    'surname', 
    'shirtType', 
    'size', 
    'teamPreference', 
  ];
}
