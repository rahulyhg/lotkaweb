<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model as Model;

class ListItem extends Model
{
  protected $table = 'list_items';
  
  protected $fillable = [
    "name",
    "type",
    "description",
    "item_lists_id",
    "taxon_id",
  ];

  public function attr()
  {
    return $this->belongsToMany('App\Models\Attribute', 'list_items_attribute')->withTimeStamps(); 
  }
  
  public function parentList()
  {
    return $this->belongsTo('App\Models\ItemList'); 
  }
  
  public function taxon()
  {
    return $this->hasOne('App\Models\Taxon'); 
  }  
}
