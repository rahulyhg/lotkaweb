<?php

namespace App\Controllers\Page\Participant;

use App\Controllers\Controller;
use Slim\Views\Twig as View;

class ParticipantPageController extends Controller
{
  public function index($request, $response, $arguments)
  {
    return $this->view->render($response, '/new/participant/dashboard.html', [
    ]);
  }
  
  public function onboarding($request, $response, $arguments)
  {
  }
    
  public function page($request, $response, $arguments)
  {
    $slug = isset($arguments['page']) ? $arguments['page'] : $arguments['category'];
    $slug = filter_var($slug, FILTER_SANITIZE_STRING);
    $post = Post::where('slug', $slug)->first();
    
    if($slug == 'tickets') {
      $this->container->view->getEnvironment()->addGlobal(
        'tickets', self::populateTicketInfo());
    }
    
    return $this->view->render($response, '/new/page.html', [
      'PUBLIC_KEY' => $this->container->get('stripe')['PUBLIC_KEY'],
      'post' => $post,
      'slug' => $slug,
    ]);
  }  
}