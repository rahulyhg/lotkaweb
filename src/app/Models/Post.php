<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model as Model;

class Post extends Model
{
  protected $table = 'posts';
  
  protected $fillable = [
    "slug",
    "description",
    "title",
    "headline",
    "content",
    "image",
    "meta",
    "category_id",
    "weight",
    "post_id",
    "publish_at",
    "unpublish_at",
    "user_id",
    "visible_to",
    "notes",
  ];
  
  public function category()
  {
      return $this->belongsTo('App\Models\Category');
  }
  
  public function user()
  {
      return $this->belongsTo('App\Models\User');
  }    
}
