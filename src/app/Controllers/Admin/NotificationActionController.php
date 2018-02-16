<?php

namespace App\Controllers\Admin;

use App\Models\Attribute;
use App\Models\Character;
use App\Models\Group;
use App\Models\Plot;
use App\Models\Relation;
use App\Models\User;
use App\Models\Notification;
use App\Controllers\Controller;
use Slim\Views\Twig as View;

class NotificationActionController extends Controller
{ 
  public function addNotificationToCurrent($request, $response, $arguments) {
    $target = Relation::inRandomOrder()->first();
        
    self::notify(self::getCurrentUser()['user'], $target, [
      'description'=>'You have been added to a relationship ' . $target->name,
      'icon'=> 'chain',
    ]);
    
    return $response->withRedirect($this->router->pathFor('participant.home'));    
  }
}