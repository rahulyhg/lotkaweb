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
use Respect\Validation\Validator as v;
use App\Mail\Sender;
use App\Mail\Templater;

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
            "self_care" =>         "Basic self-care kit (aspirin, blister band aids for your feet, medications)"
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
    $player = self::getPlayer($arguments);
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
    
  public function savePacking($request, $response, $arguments) 
  {
    $player = self::getPlayer($arguments);
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
  
  private function profileData() {
    $attributes = [
      'care_of', 
      'street_address_1', 
      'street_address_2',
      'city', 
      'postal_code', 
      'state', 
      'country', 
      
      'phone', 
      
      'emergency_contact', 
      'emergency_phone', 
      
      'birth_date', 
      'allergies', 
      'gender', 
//      'id_number_swe', 
//      'medical_conditions',
      'onefifty_plus', 
      'size', 
      'torso_circumference', 
    ];
      
    $torso_size = [['code' => 70, 'description' => 'Up to 70cm (28")']];
    for ($i = 70; $i <= 200; $i+=5) {
      $torso_size[] = [ 
        'code' => $i+5, 
        'description' => "$i to " . ($i+5) . "cm (" . ceil($i/2.54) . "-" . ceil(($i+5)/2.54) . "\")" 
      ];
    }
    $torso_size[] = ['code' => 210, 'description' => 'Over 205cm (81")'];
    
    return [
      'attributes' => $attributes,
      'user_data' => [ 'username', 'email', 'first_name', 'last_name' ],
      'genders' => ['Non-binary','Female','Male','Other'],
      'sizes' => [
        [ 'code' => 'SMALL',  'description' => "up to 165cm (<5'5\")"   ],
        [ 'code' => 'MEDIUM', 'description' => "165-175cm (5'9\")"  ],
        [ 'code' => 'LARGE',  'description' => "175-190cm (6'3\")" ],
        [ 'code' => 'XLARGE', 'description' => "over 190cm (>6'3\")" ], 
      ],
      'torso_circumference' => $torso_size,
    ];    
  }
  
  public function profile($request, $response, $arguments) 
  {
    $player = self::getPlayer($arguments);
    $user = $player["user"];
    
    return self::render(
      "profile", 
      [
        "player" => $player,
        "profile" => self::profileData(),
      ], 
      $response
    );
    
  }
  
  public function saveProfile($request, $response, $arguments) 
  {
    
    $validation = $this->validator->validate($request, [
      'email' => v::noWhitespace()->notEmpty()->emailAvailable(),
      'email' => v::noWhitespace()->notEmpty()->userAvailable(),
    ]);

    if ($validation->failed()) {
      $this->flash->addMessage('error', "This email is already in the system, please use another one.");
      return $response->withRedirect(
        isset($arguments["uid"])  ? 
        $this->router->pathFor('participant.profile', ['uid' => $arguments["uid"]]):
        $this->router->pathFor('participant.profile')      
      );
    }
    
    $player = self::getPlayer($arguments);
    $user = $player["user"];
    
    $profileData = self::profileData();

    foreach($profileData["attributes"] as $key) {
      $value = $request->getParam($key);
      $value = is_null($value) ? false : $value;
      self::setAttribute($user, $key, $value);
    }

    $credentials = [
      'email' => $request->getParam('email'),
      'username' => $request->getParam('email'),
    ];
    
    $this->container->sentinel->update($user, $credentials);
    
    if($user->save()) {
      $this->flash->addMessage('success', "Profile updated."); 
    } else {
      $this->flash->addMessage('error', "Something went wrong saving the profile, sorry."); 
    }
    
    return $response->withRedirect(
      isset($arguments["uid"])  ? 
      $this->router->pathFor('participant.profile', ['uid' => $arguments["uid"]]):
      $this->router->pathFor('participant.profile')      
    );
  }
  
  public function sendMessage($request, $response, $arguments) 
  {
    $player = self::getCurrentUser();
    $from = $player["user"];
    $to = isset($arguments["uid"]) ? $arguments["uid"] : false; //hashed
    $to = $to ? User::where('id', $to)->first() : false;
    $message = $request->getParam('message');
    
    if($to && $message) {
      $mail = new Sender($this->container->get('settings'));
      
      if($mail->message($from, $to, $message, 'message-email', true)) {
        $this->flash->addMessage('success', "Your message have been sent!");
      } else {
        $this->flash->addMessage('error', "Something went wrong when sending your message, please try again later.");
      };  
      return $response->withRedirect($this->router->pathFor('participant.home'));
    } else {
      $this->flash->addMessage('error', "Something went wrong sending your message, sorry."); 
      return $response->withRedirect($this->router->pathFor('participant.home'));
    }
  }
  
  //************
  // Helpers
  //************
  
  private function getPlayer($arguments) {
    return $this->container->auth->isWriter() && isset($arguments["uid"]) && $arguments["uid"] ?
      self::getPlayerInfo($arguments["uid"]) : self::getCurrentUser();
  }
}