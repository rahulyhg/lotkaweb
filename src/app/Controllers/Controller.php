<?php

namespace App\Controllers;

use App\Models\Post;
use App\Models\User;
use App\Models\Order;
use App\Models\Character;
use App\Models\Attribute;
use App\Models\Notification;

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
    foreach ($collection as $id => $attr) {      
      if(isset($a[$attr->name])) {
        if(!is_array($a[$attr->name])) $a[$attr->name] = [$a[$attr->name]];
        $a[$attr->name][] = $attr->value ? $attr->value : false;
      } else {     
        $a[$attr->name] = $attr->value ? $attr->value : false; 
      }
    }
    
    return $a;
  }
  
  //===========================================================================
  // Helpers
  //===========================================================================  
  
  
  public function getAttributeIds($attributes = [ 'keys' => [], 'values' => [] ]) {
    $attribute_ids = [];
    foreach ($attributes['keys'] as $i => $attr_key) {
      $attribute_ids[] = Attribute::firstOrCreate([
        'name' => $attr_key, 
        'value' => $attributes['values'][$i]
      ])->id;
    }
    return $attribute_ids;
  }
  
  # Sets Attribute of Name: Value to supplied Model instance
  # Use in controller like: self::setAttribute($user, 'npc', 'on');
  public function setAttribute($model, $name, $value) {
    if (!is_object($model)) return false;
    self::removeAttribute($model, $name);
    $attribute_id = self::getAttributeIds([
      'keys' => [$name], 'values' => [$value]
    ]);
    
    //var_dump($attribute_id, $name, $value);
    
    //echo "<br>";
    
    return $model->attr()->sync($attribute_id, false);
  }  
  
  # Removes all Attributes of Name to supplied Model instance
  # Use in controller like: self::removeAttribute($user, 'npc');
  public function removeAttribute($model, $name) {
    if (!is_object($model)) return false;
    $currentAttributes = $model->attr()->where('name', $name)->get();
    
    return count($currentAttributes) ? $model->attr()->detach($currentAttributes) : false;
  }  
  
  # See OnboardingPageController.php:266 for usage.
  public function setModelAttributes($request, $model_attribute_list, $model, $extra_attributes = []) {
    $model_attributes = self::mapAttributes($model->attr);
    #Supply extra attributes not present in the origninal request to be able to set system attributes.
    $request_attributes = array_merge( $request->getParsedBody(), $extra_attributes );    
    
    # We prepopulate boolean attributes so they can be deactivated, otherwise they are not even sent
    if( isset($request_attributes["boolean_attributes"]) ) {
      $boolean_attributes = explode(',', $request->getParam("boolean_attributes"));
      foreach ($boolean_attributes as $a) {
        $request_attributes[$a] = isset($request_attributes[$a]) ? $request_attributes[$a] : 'false';
      }
    }
    
    $attributes = [ 'keys' => [], 'values' => [] ];

    foreach ($model_attribute_list as $attr) {
      $attribute_value = null;
      
      if(array_key_exists($attr, $model_attributes)) $attribute_value = $model_attributes[$attr]; //Prepolulate existing attr
      if(isset($request_attributes[$attr])) $attribute_value = $request_attributes[$attr]; //Update if we have new values

      if ( is_array($attribute_value) ? count($attribute_value) : strlen($attribute_value) ) {
        if(is_array($attribute_value)) {
          foreach ($attribute_value as $i => $val) {
            $attributes['keys'][] = $attr;
            $attributes['values'][] = $attribute_value[$i];
          }
        } else {
          $attributes['keys'][] = $attr;
          $attributes['values'][] = $attribute_value;
        }
      }
    }

    $updated_attribute_ids = self::getAttributeIds( $attributes );
        
    return $model->attr()->sync($updated_attribute_ids);
  }  
  
  public function render($slug, $info, $response) {
    $participant = self::getCurrentUser();
    
    $visibility = ['participant'];
    if($this->container->auth->isAdmin()) $visibility[] = 'admin';
    
    $post = Post::where('slug', $slug)
      ->visibleTo($visibility)
      ->published()->first();
    
    if(!$post) {
      $post["content"] = "The page for '$slug' has not been released yet or is under development, sorry for the inconvenience.";
      if($this->container->get('settings')['renderer']['debug'])
        die($post["content"]);
    }
    
    foreach($info as $key => $data) {
      $this->container->view->getEnvironment()->addGlobal(
        $key, $data
      );
    }
    
    return $this->view->render($response, '/new/participant/page.html', [
      'dashboard' => self::dashboardSections(),
      'post' => $post,
      'current' => $participant
    ]);
  }
  
  #=====================================
  # Community Helpers
  #=====================================
  
  public function notify($user, $target, $notification = []) {    
    $ref = new \ReflectionClass(get_class($target));
    $target_name = strtolower($ref->getShortName());    
    
    $data = [];
    $data['title'] = isset($notification['title']) ? $notification['title'] : 'New ' . ucfirst($target_name);
    if ( isset($notification['description']) ) $data['description'] = $notification['description'];
    $data['type'] = $target_name;
    $data['icon'] = isset($notification['icon']) ? $notification['icon'] : $target_name;
    $data['target'] = $this->router->pathFor('participant.' . $target_name, ['uid'=> $target->id ]);
    
    return Notification::add($user, $target, $data);
  }
  
  public function markNotificationsAsSeen($model, $seen_by) {
    $notifications = $model->notifications()->where('user_id', $seen_by->id)->get();
    
    foreach($notifications as $notification) { 
      $notification->seen_at = date("Y-m-d H:i:s");
      $notification->save();      
    }
    
    $this->container->view->getEnvironment()->addGlobal(
        "userData", [ "notifications" => $seen_by->notifications()->where('seen_at', null)->get() ]
      );
    
  }
  
  public function getRelations($model) {
    $modelRelations = $model->rel();
    $r = [];
    foreach($modelRelations as $relation) {
      $r["data"] = $relation;
      $r["attributes"] = self::mapAttributes($relation->attr);
      $r["characters"] = [];
      foreach($relation->characters() as $character) {
        $r["characters"][] = self::getCharacter($character->id);
      }      
      $r["groups"] = [];
      foreach($relation->groups() as $group) {
        $r["groups"][] = self::getGroup($group->id);
      }      
    }
    
    return $r;
  }
  
  public function getGroup($uid) {
    $group = Group::where('id', $uid)->first();
    
    return $group ? [
      "data" => $group,
      "attributes" => self::mapAttributes( $group->attr ),
      "characters" => self::getGroupCharacters( $group ),
    ] : [];    
  }  
  
  public function getGroupCharacters($group) {
    $groupCharacters = $group->characters();
    
    $characterCollection = [];
    foreach($groupCharacters as $character) {
      $characterCollection[] = self::getCharacter($character->id);
    }
    return $characterCollection;    
  }  
  
  public function isNpc($character) {
    return !is_null( $character->attr->where('name','npc')->where('value','on')->first() );
  }
    
  public function getCharacter($uid) {
    $character = Character::where('id', $uid)->first();
    
    return $character && !self::isNpc($character) ? [
        "data" => $character, 
        "attributes" => self::mapAttributes($character->attr),
        "player" => self::getPlayerInfo($character->user_id),
      ] : [];
  }   
  
  public function getPlayerCharacter($user_id) {
    $player_characer = Character::where('user_id', $user_id)->orderBy('created_at', 'desc')->first();
    
    $character = [ "data" => [], "attributes" => [], "hasCharacter" => false ];
    if($player_characer) {
      $character["data"] = $player_characer;
      $character["relations"] = self::getRelations($player_characer);
      $character["attributes"] = self::mapAttributes($player_characer->attr);
      $character["hasCharacter"] = true;
    }  
    return $character;
  }
  
  public function getPlayerInfo($uid) {
    $user_data = User::where('id', $uid)->first();
      
    return $user_data? [
      "user" => $user_data,
      "attributes" => self::mapAttributes( $user_data->attr ),
      "order" => Order::where('user_id', $user_data->id)->first(),
      "character" => self::getPlayerCharacter($user_data->id),
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
      "order" => Order::where('user_id', $participant->id)->first(),
      "character" => self::getPlayerCharacter($participant->id),
    ] : [];
  }
  
  #TODO: Move this to a menu controller
  public function dashboardSections() {
    $participant = self::getCurrentUser();
    
    $menu = [
      'sections' => [

          'players' => [
            'title' => 'Participants',
            'target' => $this->router->pathFor('participant.character.list'),
            'pages' => [
              'players' => [
                'title' => 'Participant List',
                'target' => $this->router->pathFor('participant.character.list'),
                'info' => 'Thermopylae Population Directory',
                'image' => '/img/dashboard/' . 'player-list.jpg'
              ],
              'gallery' => [
                'title' => 'Participant Gallery',
                'target' => $this->router->pathFor('participant.character.gallery'),
                'info' => 'Outpost Thermopylae Population Gallery',
                'image' => '/img/dashboard/' . 'player-gallery.jpg'
              ],
            ]
          ],
        
      ]
    ];
    
    if($participant["character"]["hasCharacter"]) {
      $menu["sections"]["character"] = [
        'title' => 'My Character',
        'target' => $this->router->pathFor('participant.character.my'),
        'pages' => [
          'editor' => [
            'title' => 'Character Editor',
            'target' => $this->router->pathFor('participant.character.my'),
            'info' => 'My character Editor',
            'image' => isset($participant["attributes"]["portrait"]) ? '/assets/portraits/scaled/' . $participant["attributes"]["portrait"] : '/images/logos/lotka-volterra-logo.svg',
          ],
          'presentetaion' => [
            'title' => 'Character Presentation',
            'target' => $this->router->pathFor('participant.character', [ 'uid' => $participant["character"]["data"]->id ]),
            'info' => 'My character presentation',
            'image' => '/assets/images/logos/outposts/outposts.svg#theta',
          ],
        ]
      ];
      
      $menu["sections"]["relationships"] = [
        'title' => 'Relationships',
        'target' => $this->router->pathFor('participant.relation.list'),
        'pages' => [
          'relationships' => [
            'title' => 'My Relationships',
            'target' => $this->router->pathFor('participant.relation.my'),
            'info' => 'My characters relationships',
            'image' => '/img/dashboard/' . 'my-relationships.jpg'
          ],
         'pending' => [
            'title' => 'Pending Relationships',
            'target' => $this->router->pathFor('participant.relation.pending'),
            'info' => 'Your pending relationships',
            'image' => '/img/dashboard/' . 'pending-relationships.jpg'
          ],         
          'list' => [
            'title' => 'Outpost Relationships',
            'target' => $this->router->pathFor('participant.relation.list'),
            'info' => 'Public relationships',
            'image' => '/img/dashboard/' . 'relationships.jpg'
          ],
        ]
      ];
    }

/*        
          'my' => [
            'title' => 'My Pages',
            'pages' => [
              'character' => [
                'title' => 'My Character',
                'target' => $this->router->pathFor('participant.character.my'),
                'info' => 'My character page',
                'image' => '/assets/portraits/scaled/' . $participant["attributes"]["portrait"]
              ],
              'relationships' => [
                'title' => 'My Relationships',
                'target' => $this->router->pathFor('participant.relation.my'),
                'info' => 'My characters relationships',
                'image' => '/img/dashboard/' . 'my-relationships.jpg'
              ],
              'plots' => [
                'title' => 'My Plots',
                'target' => $this->router->pathFor('participant.plot.my'),
                'info' => 'My characters plots, and plots that my team or groups are involved in',
                'image' => '/img/dashboard/' . 'my-plots.jpg'
              ],
              'groups' => [
                'title' => 'My Groups',
                'target' => $this->router->pathFor('participant.group.my'),
                'info' => 'Groups that I\'m part of',
                'image' => '/img/dashboard/' . 'my-groups.jpg'
              ],
              'schedules' => [
                'title' => 'My Schedule',
                'target' => $this->router->pathFor('participant.schedules.my'),
                'info' => 'My work schedule',
                'image' => '/img/dashboard/' . 'my-schedules.jpg'
              ],
              'check_list' => [
                'title' => 'Check list',
                'target' => $this->router->pathFor('participant.schedules.my'),
                'info' => 'The things you need to have done before attending',
                //'image' => '/img/dashboard/' . 'my-schedules.jpg'
              ],
              'reading' => [
                'title' => 'Required reading',
                'target' => $this->router->pathFor('participant.schedules.my'),
                'info' => 'Books and articles you should read before Lotka-Volterra',
                //'image' => '/img/dashboard/' . 'my-schedules.jpg'
              ],
            ],
          ],
        
          'characters' => [
            'title' => 'Participants',
            'target' => $this->router->pathFor('participant.character.list'),
            'pages' => [
              'players' => [
                'title' => 'Participant List',
                'target' => $this->router->pathFor('participant.player.list'),
                'info' => 'Participant list',
                'image' => '/img/dashboard/' . 'player-list.jpg'
              ],
              'gallery' => [
                'title' => 'Participant Gallery',
                'target' => $this->router->pathFor('participant.player.gallery'),
                'info' => 'Participant profile image gallery',
                'image' => '/img/dashboard/' . 'player-gallery.jpg'
              ],
              'characters' => [
                'title' => 'Character List',
                'target' => $this->router->pathFor('participant.character.list'),
                'info' => 'Get the characters in list form',
                'image' => '/img/dashboard/' . 'character-list.jpg'
              ],
              'gallery' => [
                'title' => 'Character Gallery',
                'target' => $this->router->pathFor('participant.character.gallery'),
                'info' => 'Character profile images',
                'image' => '/img/dashboard/' . 'character-gallery.jpg'
              ],
            ]
          ],

          'relationships' => [
            'title' => 'Relationships',
            'target' => $this->router->pathFor('participant.relation.list'),
            'pages' => [
              'list' => [
                'title' => 'Relationships',
                'target' => $this->router->pathFor('participant.relation.list'),
                'info' => 'Public relationships',
                'image' => '/img/dashboard/' . 'relationships.jpg'
              ],
             'pending' => [
                'title' => 'Pending Relationships',
                'target' => $this->router->pathFor('participant.relation.pending'),
                'info' => 'Your pending relationships and public relationship requests',
                'image' => '/img/dashboard/' . 'pending-relationships.jpg'
              ],              
            ]
          ],
        
          'plots' => [
            'title' => 'Plots',
            'target' => $this->router->pathFor('participant.plot.list'),
            'pages' => [
              'list' => [
                'title' => 'Plots',
                'target' => $this->router->pathFor('participant.plot.list'),
                'info' => 'Public plots list',
                'image' => '/img/dashboard/' . 'plots.jpg'
              ],
            ]
          ],
        
          'groups' => [
            'title' => 'Groups',
            'target' => $this->router->pathFor('participant.group.list'),
            'pages' => [
              'list' => [
                'title' => 'Groups',
                'target' => $this->router->pathFor('participant.group.list'),
                'info' => 'Public groups',
                'image' => '/img/dashboard/' . 'groups.jpg'
              ],
            ]
          ],
        
          'schedules' => [
            'title' => 'Schedules',
            'target' => $this->router->pathFor('participant.schedules'),
            'pages' => [
              'list' => [
                'title' => 'Schedules',
                'target' => $this->router->pathFor('participant.schedules'),
                'info' => 'Schedule lists',
                'image' => '/img/dashboard/' . 'schedules.jpg'
              ],
            ]
          ],
*/    
    
    return $menu;
  }  
}
