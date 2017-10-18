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

use App\Controllers\Admin\UserActionController;

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
    if(!isset($participant["attributes"]["onboarding_complete"])) 
      return $response->withRedirect($this->router->pathFor('participant.onboarding'));
    
    return $this->view->render($response, '/new/participant/dashboard.html', [
      'debug' => $participant
    ]);
  }
  
  public function page($request, $response, $arguments)
  {
    $slug = filter_var($arguments['page'], FILTER_SANITIZE_STRING);
    $post = Post::where('slug', $slug)->first();
    
    return $this->view->render($response, '/new/participant/page.html', [
      'post' => $post
    ]);
  }
  
  #Onboarding process
  public function onboarding($request, $response, $arguments)
  {
    $participant = self::getCurrentUser();
    $stage = isset($participant["attributes"]["onboarding_stage"]) ?
        $participant["attributes"]["onboarding_stage"] : 1;
    
    return $this->view->render($response, '/new/participant/onboarding/stages.html', [
      'stage' => $stage
    ]);
  }

  public function save_stage($request, $response, $arguments)
  {
    $participant = self::getCurrentUser();
    $stage = isset($participant["attributes"]["onboarding_stage"]) 
        && $participant["attributes"]["onboarding_stage"] == $arguments['stage'] ?
        $participant["attributes"]["onboarding_stage"] : 1;
    
    return $this->view->render($response, '/new/participant/onboarding/stages.html', [
      'stage' => $stage
    ]);    
  }    
}