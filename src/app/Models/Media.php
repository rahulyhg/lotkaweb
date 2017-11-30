<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model as Model;

class Media extends Model
{
  protected $table = 'media';
  
  protected $fillable = [
    "name",
    "description",
    "filename",
    "visible_to",
    "publish_at",
    "unpublish_at",
    "notes",
  ];
  
  public function attr()
  {
      return $this->belongsToMany('App\Models\Attribute', 'media_attribute')->withTimeStamps();    
  }  
  
  /**
   * Scope a query to only include published media.
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
      return $query->where('visible_to', 'like', '%' . $type . '%')
        ->orWhere('visible_to', '');
  }
  
}
