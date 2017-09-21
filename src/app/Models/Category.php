<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model as Model;

class Category extends Model
{
  protected $table = 'post_categories';
  
  protected $fillable = [
    "slug",
    "description",
    "name",
  ];
  
  public function posts()
  {
      return $this->hasMany('App\Models\Post');
  }
}
