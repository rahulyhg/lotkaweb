<?php

namespace App\Controllers\Page\Participant;

use App\Models\Post;
use App\Models\Character;
use App\Models\Plot;
use App\Models\Group;
use App\Models\Relation;
use App\Models\Attribute;
use App\Models\Task;

use App\Controllers\Controller;
use Slim\Views\Twig as View;

class ParticipantPageController extends Controller
{
  public function index($request, $response, $arguments)
  {
    $participant = $this->container->sentinel->getUser();
    
    
    return $this->view->render($response, '/new/participant/dashboard.html', [
      'debug' => $participant
    ]);
  }
  
  public function onboarding($request, $response, $arguments)
  {
  }
    
  public function page($request, $response, $arguments)
  {
    $slug = filter_var($arguments['page'], FILTER_SANITIZE_STRING);
    $post = Post::where('slug', $slug)->first();
    
    return $this->view->render($response, '/new/participant/page.html', [
      'post' => $post
    ]);
  }  
}