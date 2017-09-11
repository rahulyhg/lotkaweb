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
    'permissions'
  ];

  protected $loginNames = ['email', 'username'];
  
  public function tasks()
  {
      return $this->hasMany('App\Models\Task');
  }  
}
