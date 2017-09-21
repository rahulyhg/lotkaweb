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
  
  public function attr()
  {
      return $this->belongsToMany('App\Models\Attribute', 'ticket_attribute')->withTimeStamps(); 
  }  
}
