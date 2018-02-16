<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model as Model;

class Notification extends Model
{
  protected $table = 'notifications';
  
  protected $fillable = [
    'title',
    'description',
    'email',
    'seen_at',
    'dismissed_at',
    'user_id',
  ];
  
  public function attr()
  {
      return $this->belongsToMany('App\Models\Attribute', 'notification_attribute'); 
  }
  public function user()
  {
      return $this->belongsTo('App\Models\User', 'user_id'); 
  }

  public function groups()
  {
      return $this->belongsTo('App\Models\Group', 'notification_group'); 
  }
  
  public function plots()
  {
      return $this->belongsTo('App\Models\Plot', 'notification_plot'); 
  }
  
  public function relations()
  {
      return $this->belongsTo('App\Models\Relation', 'notification_relation'); 
  }
  
  
  
  public function add($user, $target, $data = ['title'=>'', 'description'=>'', 'type'=>'', 'icon'=>'', 'target' => ''])
  {    
    $notification = Notification::create([
      'title' => $data['title'], 
      'description' => $data['description']
    ]);
    $notification->user()->associate($user);
    $target->notifications()->attach($notification->id);
    $r = $notification->save();
    
    \App\Controllers\Controller::setAttribute($notification, 'type', $data['type']);
    \App\Controllers\Controller::setAttribute($notification, 'target', $data['target']);
    \App\Controllers\Controller::setAttribute($notification, 'icon', $data['icon']);
    
    return $r;
  }
  
  public function see($notification_id) {
    $notification = Notification::find($notification_id);
    $notification->seen_at = date("Y-m-d H:i:s");
    return Notification::save();
  }
  
  public function dismiss($notification_id) {
    $notification = Notification::find($notification_id);
    $notification->dismissed_at = date("Y-m-d H:i:s");
    return Notification::save();
  }
  
  public function removeDissmissed() {
    return Notification::where('dismissed_at', '<>', null)->delete();    
  }  
}
