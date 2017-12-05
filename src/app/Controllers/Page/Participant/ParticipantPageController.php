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
    $post = Post::where('slug', $slug)->first();
    
    return $this->view->render($response, '/new/participant/page.html', [
      'post' => $post,
      'current' => $participant
    ]);
  }  
}