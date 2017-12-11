<?php

namespace App\Controllers;

use App\Models\Post;
use App\Models\User;
use App\Models\Order;
use App\Models\Attribute;

class Controller
{
  protected $container;

  public function __construct($container)
  {
    $this->container = $container;
  }

  public function __get($property)
  {
    if ($this->container->{$property}) {
      return $this->container->{$property};
    }
  }
  
  public function paramToArray($request, $key) {
    $param = $request->getParam($key);
    return is_array($param) ? $param : [$param];
  }
  
  public function mapAttributes($collection) {
    $a = [];
    foreach ($collection as $name => $value) {
      if(isset($a[$value->name])) {
        if(!is_array($a[$value->name])) $a[$value->name] = [$a[$value->name]];
        $a[$value->name][] = $value->value;
      } else {     
        $a[$value->name] = $value->value; 
      }
    }        
    return $a;
  }
  
  # Helpers
  public function render($slug, $info, $response) {
    $participant = self::getCurrentUser();
    $post = Post::where('slug', $slug)
      ->visibleTo(['participant', 'admin'])
      ->published()->first();
    
    if(!$post) die("Template '$slug' is missing, have a nice day.");
    
    $this->container->view->getEnvironment()->addGlobal(
      $info['key'], $info['data']
    );
    
    return $this->view->render($response, '/new/participant/page.html', [
      'post' => $post,
      'current' => $participant
    ]);
  }
  
  public function getPlayerInfo($uid) {
    $user_data = User::where('id', $uid)->first();

    return $user_data? [
      "user" => $user_data,
      "attributes" => self::mapAttributes( $user_data->attr ),
      "order" => Order::where('user_id', $user_data->id)->first()
    ] : [];
  }
  
  public function getCurrentUser() {
    $participant = User::where(
      'username',
      $this->container->sentinel->getUser()->username
    )->first();
    
    return $participant ? [
      "user" => $participant,
      "attributes" => self::mapAttributes( $participant->attr ),
      "order" => Order::where('user_id', $participant->id)->first()
    ] : [];
  }    
}
