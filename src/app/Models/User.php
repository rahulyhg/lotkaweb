<?php

namespace App\Models;

use Cartalyst\Sentinel\Users\EloquentUser as SentinelUser;

class User extends SentinelUser
{
  protected $table = 'users';
  
  protected $fillable = [
    'username',
    'displayname',
    'email',
    'password',
    'first_name',
    'last_name',
    'org_notes',
    'permissions',
    'character_id',
    'hash',
  ];

  protected $loginNames = ['email', 'username'];
  
  public function tasks()
  {
      return $this->hasMany('App\Models\Task');
  }
  
  public function character()
  {
      return $this->hasOne('App\Models\Character', 'user_id');
  }
  
  public function groups()
  {
      return $this->belongsToMany('App\Models\Group', 'user_group')->withTimeStamps(); 
  }
  
  public function attr()
  {
      return $this->belongsToMany('App\Models\Attribute', 'user_attribute')->withTimeStamps(); 
  }
  
  public function order()
  {
      return $this->belongsTo('App\Models\Order');
  }  
}
