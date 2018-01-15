<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model as Model;

class Character extends Model
{
  protected $table = 'characters';
  
  protected $fillable = [
    "name",
    "description",
    "user_id",
  ];
  
  public function attr()
  {
    return $this->belongsToMany('App\Models\Attribute', 'character_attribute')->withTimeStamps(); 
  }
  
  public function user()
  {
    return $this->hasOne('App\Models\User');
  }
  
  public function groups()
  {
    return $this->belongsToMany('App\Models\Group', 'character_group')->withTimeStamps();
  }
  
  public function plots()
  {
    return $this->belongsToMany('App\Models\Plot', 'character_plots')->withTimeStamps(); 
  }
  
  public function rel()
  {
    return $this->belongsToMany('App\Models\Relation', 'character_relation')->withTimeStamps(); 
  }    
}
