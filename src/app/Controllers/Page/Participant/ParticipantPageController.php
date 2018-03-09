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
  
  private function packing_lists()
  {
    return [
        "must_have" => [
          "title" => "Items you need to bring",
          "items" => [
            "sleeping_bag" =>      "Sleeping Bag",
            "sleeping_mat" =>      "Sleeping Mat",
            "hygiene_articles" =>  "Hygiene Articles",
            "boots" =>             "Boots",
            "pants" =>             "Pants/trousers, preferrably with leg-pockets like cargo pants",
            "socks" =>             "Socks (bring extra pairs)",
            "sweater" =>           "Warm, neutral non-patterned sweater (knitted or similar)",
            "gloves" =>            "Work gloves",
            "underwear" =>         "Underwear",
            "tshirt" =>            "Neutral t-shirt or tank top",
            "undergarments" =>     "Warm undergarments (especially if you think you might end up topside).",
            "self_care" =>         "Basic self-care kit (aspirin, blister band aids for your feet)"
          ],
        ],

        "nice_to_have" => [
          "title" => "Nice to have items",
          "items" => [
            "memorabilia" =>      "Memorabilia",
            "earlpugs" =>         "Earplugs",
            "cash" =>             "Cash for the bar on saturday (sek or euro)",
            "snacks" =>           "Snacks and or food for thursday. It will be a long day.",
            "flashlight" =>       "Torch/Flashlight",
          ],
        ],
    ];
  }

  public function packing($request, $response, $arguments) 
  {
    $player = $this->container->auth->isWriter() && isset($arguments["uid"]) &&  $arguments["uid"] ?
      self::getPlayerInfo($arguments["uid"]) : self::getCurrentUser();
    $user = $player["user"];
    
    return self::render(
      "packing-list", 
      [
        "user" => $user,
        "packing_lists" => self::packing_lists(),
      ], 
      $response
    );
  }
    
  public function save_packing($request, $response, $arguments) 
  {
    $player = $this->container->auth->isWriter() && isset($arguments["uid"]) && $arguments["uid"] ?
      self::getPlayerInfo($arguments["uid"]) : self::getCurrentUser();
    $user = $player["user"];
    
    $packing_lists = self::packing_lists();
    
    $packing_attributes = array_merge(
      array_keys($packing_lists["must_have"]["items"]),
      array_keys($packing_lists["nice_to_have"]["items"]),
      ["pnqs", "ta"]
    );
    
    foreach($packing_attributes as $key) {
      $value = $request->getParam($key);
      $value = is_null($value) ? false : $value;
      $debug[] = self::setAttribute($user, "packing_$key", $value);
    }
    
    if($user->save()) {
      $this->flash->addMessage('success', "Packing list updated."); 
    } else {
      $this->flash->addMessage('error', "Something went wrong saving the packing list, sorry."); 
    }

    return $response->withRedirect(
      isset($arguments["uid"])  ? 
      $this->router->pathFor('participant.packing', ['uid' => $arguments["uid"]]):
      $this->router->pathFor('participant.packing')
    );
  }  
}