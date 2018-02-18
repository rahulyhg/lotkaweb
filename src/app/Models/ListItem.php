<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model as Model;

class ListItem extends Model
{
  protected $table = 'list_item';
  
  protected $fillable = [
    "name",
    "type",
    "description",
    "list_id",
    "taxon_id",
  ];

  public function attr()
  {
    return $this->belongsToMany('App\Models\Attribute', 'list_attribute')->withTimeStamps(); 
  }
  
  public function parentList()
  {
    return $this->belongsTo('App\Models\List'); 
  }
  
  public function taxon()
  {
    return $this->hasOne('App\Models\Taxon'); 
  }  
}
