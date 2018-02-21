<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model as Model;

class ItemList extends Model
{
  protected $table = 'item_lists';
  
  protected $fillable = [
    "name",
    "description",
    "character_id",
  ];
  
  public function attr()
  {
    return $this->belongsToMany('App\Models\Attribute', 'item_lists_attribute')->withTimeStamps(); 
  }
  
  public function character()
  {
    return $this->belongsTo('App\Models\Character');
  }
  
  public function items()
  {
      return $this->hasMany('App\Models\ListItems');
  }  
}
