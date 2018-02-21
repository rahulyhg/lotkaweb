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

  public function notifications()
  {
      return $this->belongsToMany('App\Models\Notification', 'notification_attribute')->withTimeStamps();
  }
  public function characters()
  {
      return $this->belongsToMany('App\Models\Character', 'character_attribute')->withTimeStamps();
  }
  public function groups()
  {
      return $this->belongsToMany('App\Models\Group', 'group_attribute')->withTimeStamps();
  }
  public function plots()
  {
      return $this->belongsToMany('App\Models\Plot', 'plot_attribute')->withTimeStamps();
  }
  public function posts()
  {
      return $this->belongsToMany('App\Models\Post', 'post_attribute')->withTimeStamps();
  }  
  public function rel()
  {
      return $this->belongsToMany('App\Models\Relation', 'relation_attribute')->withTimeStamps();
  }
  public function tickets()
  {
      return $this->belongsToMany('App\Models\Ticket', 'ticket_attribute')->withTimeStamps();
  }
  public function users()
  {
      return $this->belongsToMany('App\Models\User', 'user_attribute')->withTimeStamps();
  }
  public function media()
  {
      return $this->belongsToMany('App\Models\Media', 'media_attribute')->withTimeStamps();
  }
  public function lists()
  {
      return $this->belongsToMany('App\Models\ItemList', 'item_list_attribute')->withTimeStamps();
  }  
  public function listItems()
  {
      return $this->belongsToMany('App\Models\ListItem', 'list_item_attribute')->withTimeStamps();
  }  
}
