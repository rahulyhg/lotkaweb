<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model as Model;

class Group extends Model
{
  protected $table = 'groups';
  
  protected $fillable = [
    "name",
    "description",
  ];
  
  public function attr()
  {
      return $this->belongsToMany('App\Models\Attribute', 'group_attribute')->withTimeStamps(); 
  }
  
  public function users()
  {
      return $this->belongsToMany('App\Models\User', 'user_group')->withTimeStamps(); 
  }
  
  public function characters()
  {
      return $this->belongsToMany('App\Models\Character', 'character_group')->withTimeStamps(); 
  }
  
  public function groups()
  {
      return $this->belongsToMany('App\Models\Group', 'group_group', 'group_id', 'parent_group_id')->withTimeStamps(); 
  }
  
  public function plots()
  {
      return $this->belongsToMany('App\Models\Plot', 'group_plot')->withTimeStamps(); 
  }
  
  public function rel()
  {
      return $this->belongsToMany('App\Models\Relation', 'group_relation')->withTimeStamps(); 
  }    
}
