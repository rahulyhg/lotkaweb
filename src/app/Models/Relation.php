<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model as Model;

class Relation extends Model
{
  protected $table = 'relations';
  
  protected $fillable = [
    "name",
    "description",
  ];
  
  public function notifications()
  {
      return $this->belongsToMany('App\Models\Notification', 'notification_relation')->withTimeStamps();
  }  
  public function attr()
  {
      return $this->belongsToMany('App\Models\Attribute', 'relation_attribute')->withTimeStamps(); 
  }

  public function characters()
  {
      return $this->belongsToMany('App\Models\Character', 'character_relation')->withTimeStamps(); 
  }
  
  public function groups()
  {
      return $this->belongsToMany('App\Models\Group')->withTimeStamps(); 
  }
}
