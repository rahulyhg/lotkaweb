<?php

namespace App\Controllers\Page\Participant;

use App\Models\Post;
use App\Models\Character;
use App\Models\Plot;
use App\Models\Group;
use App\Models\Relation;
use App\Models\Attribute;
use App\Models\User;
use App\Models\Task;

use App\Controllers\Controller;
use Slim\Views\Twig as View;

class ParticipantPageController extends Controller
{
  private function getCurrentUser() {
    $participant = User::where('username', $this->container->sentinel->getUser()->username)->first();
    
    return [
      "user" => $participant,
      "attributes" => self::mapAttributes( $participant->attr ),
    ];
  }
 
  private function getPlayersInfo() {
    $role = $this->container->sentinel->findRoleBySlug('participant');
    $users = $role->users()->get();
    foreach ($users as $user) {        
      $user_obj = User::where('id', $user->id)->first();
      $user_list[] = [
        "user" => $user_obj,
        "attributes" => self::mapAttributes( $user_obj->attr ),
        "order" => $user_obj->order,
      ];
    }
    
    return $user_list;
  }
  
  private function getSlugInfo($slug) {
    /*-------------------------
    + Populate values for specific slugs 
    -------------------------*/
    switch ($slug) {
      case "players":
         return self::getPlayersInfo();
        break;
    }
    
    return [];
  }
  
  public function index($request, $response, $arguments)
  {
    $participant = self::getCurrentUser();
    
    if(!isset($participant["attributes"]["onboarding_complete"])) {
      return $response->withRedirect($this->router->pathFor('participant.onboarding', ['hash' => $participant["user"]->hash]));      
    }
    
    return $this->view->render($response, '/new/participant/dashboard.html', [
      'current' => $participant,
      'sections' => Post::whereIn('slug', [
                      'players','relationships','character',
                      'plots','groups','schedules','profile'
                    ])->get(),
    ]);
  }

  public function page($request, $response, $arguments)
  {
    $participant = self::getCurrentUser();
    $slug = filter_var($arguments['page'], FILTER_SANITIZE_STRING);
    $post = Post::where('slug', $slug)->visibleTo(['participant', 'admin'])->published()->first();
    
    if(!$post) return $response->withRedirect($this->router->pathFor('participant.home'));
    
    //Slug info will be populated under the same key as the slug name
    $this->container->view->getEnvironment()->addGlobal(
      $slug, self::getSlugInfo($slug)
    );
    
    return $this->view->render($response, '/new/participant/page.html', [
      'post' => $post,
      'current' => $participant
    ]);
  }  
}