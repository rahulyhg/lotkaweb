<?php

namespace App\Auth;

use \App\Models\User;
use \App\Models\Roles;

class Auth
{
  protected $container;

  public function __construct($container)
  {
    $this->container = $container;
  }

  public function isAdmin()
  {
    if ($this->container->sentinel->getUser()) {
      return $this->container->sentinel->getUser()->inRole('admin');
    }
  }
  
  public function isWriter()
  {
    if ($this->container->sentinel->getUser()) {
      return $this->container->sentinel->getUser()->inRole('writer') || self::isAdmin();
    }
  }

  public function isParticipant()
  {
    if ($this->container->sentinel->getUser()) {
      return $this->container->sentinel->getUser()->inRole('participant') || self::isWriter();
    }
  }
    
  public function roles()
  {
    $roles = Roles::all();
    return $roles;
  }
}
