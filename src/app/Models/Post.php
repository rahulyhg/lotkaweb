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
  
  public function attr()
  {
      return $this->belongsToMany('App\Models\Attribute', 'post_attribute')->withTimeStamps();    
  }  
  
  public function category()
  {
      return $this->belongsTo('App\Models\Category');
  }
  
  public function user()
  {
      return $this->belongsTo('App\Models\User');
  }
  
  /**
   * Scope a query to only include published posts.
   *
   * @param \Illuminate\Database\Eloquent\Builder $query
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function scopePublished($query)
  {
      return $query->where([
          ['publish_at', '<', date("Y-m-d H:i:s")],
        ])->where(function ($query) {
          $query->where('unpublish_at', '>', date("Y-m-d H:i:s"))
              ->orWhere('unpublish_at','0000-00-00 00:00:00');
        });
  }
  
  /**
   * Scope a query to only show a specific type of user.
   *
   * @param \Illuminate\Database\Eloquent\Builder $query
   * @param mixed $type
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function scopeVisibleTo($query, $type)
  {
    $query->where('visible_to', '');
      
    $groups = is_array ($type) ? $type : [$type];    
    $visible_to = array_map( 
            function($group) use ($query) { 
              $query->orWhere('visible_to', 'like', '%' . $group . '%');
              return $group;
            },
            $groups 
    );
    
    return $query;
  }  
}
