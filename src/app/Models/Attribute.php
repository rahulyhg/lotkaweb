<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model as Model;

class Attribute extends Model
{
  protected $table = 'attributes';
  
  protected $fillable = [
    "name",
    "value",
  ];
  
  public function characters()
  {
      return $this->belongsToMany('App\Models\Character')->withTimeStamps();
  }
  public function groups()
  {
      return $this->belongsToMany('App\Models\Group')->withTimeStamps();
  }
  public function plots()
  {
      return $this->belongsToMany('App\Models\Plot')->withTimeStamps();
  }
  public function posts()
  {
      return $this->belongsToMany('App\Models\Post')->withTimeStamps();
  }  
  public function rel()
  {
      return $this->belongsToMany('App\Models\Relation')->withTimeStamps();
  }
  public function tickets()
  {
      return $this->belongsToMany('App\Models\Ticket')->withTimeStamps();
  }
  public function users()
  {
      return $this->belongsToMany('App\Models\User')->withTimeStamps();
  }
  public function media()
  {
      return $this->belongsToMany('App\Models\Media')->withTimeStamps();
  }
}
