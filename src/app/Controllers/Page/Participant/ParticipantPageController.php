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
use App\Models\Order;

use App\Controllers\Controller;
use Slim\Views\Twig as View;

class ParticipantPageController extends Controller
{ 
  private function getPlayersInfo() {    
    $role = $this->container->sentinel->findRoleBySlug('participant');
    $users = $role->users()->orderBy('displayname', 'ASC')->get();
    $user_list = [];
      
    foreach ($users as $user) {        
      $user_list[] = self::getPlayerInfo($user->id);
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
  
  public function index($request, $response, $arguments) // Dashboard
  {
    $participant = self::getCurrentUser();
    
    if(!isset($participant["attributes"]["onboarding_complete"])) {
      return $response->withRedirect($this->router->pathFor('participant.onboarding', ['hash' => $participant["user"]->hash]));
    }
    
    //Dashboard sections
    $this->container->view->getEnvironment()->addGlobal('dashboard', self::dashboardSections()); #In Controller.php for now
    
    return $this->view->render($response, '/new/participant/dashboard.html', [
      'current' => $participant,
    ]);
  }

  public function page($request, $response, $arguments)
  {
    $participant = self::getCurrentUser();
    $slug = filter_var($arguments['page'], FILTER_SANITIZE_STRING);
    
    $visibility = ['participant'];
    if($this->container->auth->isAdmin()) $visibility[] = 'admin';
    
    $post = Post::where('slug', $slug)
        ->visibleTo($visibility)
        ->published()->first();
    
    if(!$post) return $response->withRedirect($this->router->pathFor('participant.home'));
    
    //Slug info will be populated under the same key as the slug name
    $this->container->view->getEnvironment()->addGlobal(
      $slug, self::getSlugInfo($slug)
    );
    
    //Dashboard sections
    $this->container->view->getEnvironment()->addGlobal('dashboard', self::dashboardSections()); #In Controller.php for now
    
    return $this->view->render($response, '/new/participant/page.html', [
      'post' => $post,
      'current' => $participant
    ]);
  }  
}