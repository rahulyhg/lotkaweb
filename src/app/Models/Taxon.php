<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model as Model;

class Taxon extends Model
{
  protected $table = 'taxons';
  
  protected $fillable = [
    "name",
    "code",
    "taxon_id",
  ];

  public function category()
  {
    return $this->belongsTo('App\Models\Taxon'); 
  }
  
  public function subcategories()
  {
    return $this->hasMany('App\Models\Taxon'); 
  }
}