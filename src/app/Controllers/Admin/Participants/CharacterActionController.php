<?php

namespace App\Controllers\Admin\Participants;

use App\Models\Character;
use App\Models\Attribute;
use App\Models\Group;
use App\Models\User;
use App\Models\Order;
use App\Models\Relation;
use App\Models\Plot;

use App\Controllers\Controller;
use Respect\Validation\Validator as v;
use Slim\Views\Twig as View;

use App\Mail\Sender;
use App\Mail\Templater;

class CharacterActionController extends Controller
{

  public function characterAttributes() {
    return [
      'age','bunk_budy','contacts_in_haven','dir_avo','dir_avo_note','gender',
      'haven_history','haven_id','history','how_survived','iso_int','iso_int_note',
      'lib_col','lib_col_note','log_int','log_int_note','mal_con','mal_con_note',
      'mil_dem','mil_dem_note','nickname','nos_pro','nos_pro_note','notes','npc',
      'org','others_vision','personal_property_items','personnel_file','phy_non',
      'phy_non_note','pronoun','quote','reviewed','role','self_vision','shift',
      'submitted_for_review','synopsis','time_in_thermopylae','traits','traumas',
    ];
  }
  
  private function characterOptions() {
    return [
      'users' => User::orderBy('displayname')->get(),
      'characters' => Character::orderBy('name')->get(),
      'groups' => Group::orderBy('name')->get(),
      'plots' => Plot::orderBy('name')->get(),
      'relations' => Relation::orderBy('name')->get(),
      'shifts' => self::characterShifts(),
      'orgs' => self::characterOrgs(),
      'set_attr' => self::characterAttributes(),
      'genders' => ['Non-binary','Female','Male','Other'],
    ];
  }
  
  private function handlePostData($request) {
    $credentials = [
      'name' => $request->getParam('name'),
      'description' => $request->getParam('description'),
      'user_id' => $request->getParam('user_id'),
    ];   
    
    $attributes = [ 
      'keys' => $request->getParam('attrKey'), 
      'values' => $request->getParam('attrVal')
    ];
    
    foreach (self::characterAttributes() as $attr) {
      if ( strlen($request->getParam($attr)) ) {
        $attributes['keys'][] = $attr;
        $attributes['values'][] = $request->getParam($attr);
      }
    }
    
    $attribute_ids = [];
    
    foreach ($attributes['keys'] as $i => $attr_key) {
      $attribute_ids[] = Attribute::firstOrCreate([
        'name' => $attr_key, 
        'value' => $attributes['values'][$i]
      ])->id;
    }
    
    if($request->getParam('new_org')) {
      $attribute_ids[] = Attribute::firstOrCreate([
        'name' => 'org', 
        'value' => $request->getParam('new_org')
      ])->id;
    }
    
    if($request->getParam('new_shift')) {
      $attribute_ids[] = Attribute::firstOrCreate([
        'name' => 'shift', 
        'value' => $request->getParam('new_shift')
      ])->id;
    }   
    
    $groups = $request->getParam('group_ids');
    $groups = is_array($groups) ? $groups : [$groups];
    
    $relations = $request->getParam('relations_ids');
    $relations = is_array($relations) ? $relations : [$relations];
    
    return [ 
      'values' => $credentials, 
      'attributes' => $attribute_ids,
      'groups' => $groups,
      'relations' => $relations,
    ];
  }
  
  private function saveCharacter($id, $data, $arguments) {
    $item = Character::create();
    if($item->id) {
      $attribute_ids = [];
      foreach ($data as $name => $value) {
        $attribute_ids[] = Attribute::firstOrCreate([
          'name' => $name, 
          'value' => $value
        ])->id;
      }

      $item->attr()->sync($attribute_ids);
      return true;
    } else {
      $this->flash->addMessage('error', "The character ID:" . $id . " could not be saved.");
      return false;
    }
  }
  
  private function removeCharacterFromUsers($character) {
    $users = User::where('id', $character->user_id)->get();      
    foreach ($users as $user) {
      $user->character_id = 0;
      $user->save();
    }
  }
  
  private function save($requestData, $request, $response, $arguments) {
    // update data
    $item = Character::firstOrCreate(['id' => $arguments['uid']]);
    $user = false;
    
    if(is_null($requestData['values']['user_id'])) {
      $user = User::where('id', $item->user_id)->first();      
      if($user) {
        $user->character_id = 0;
        $user->save();
      }
    } elseif($requestData['values']['user_id'] != $item->user_id) {
      self::removeCharacterFromUsers($item);
      
      $user = User::where('id', $requestData['values']['user_id'])->first();
      if($user) {
        $user->character_id = $item->id;
        $order = Order::where('user_id', $user->id)->first();
                
        if($order && strlen($requestData['values']['name']) == 0) {
          $requestData['values']['name'] = $order->name;
        }

        if($user_group = $user->attr->where('name', 'group')->first()) {
          $requestData['attributes'][] = Attribute::firstOrCreate([
                                          'name' => 'org', 
                                          'value' => $user_group->value
                                        ])->id;
        }
        if($user_role = $user->attr->where('name', 'role')->first()) {
          $requestData['attributes'][] = $user_role->id;
        }
        if($user_shift = $user->attr->where('name', 'shift')->first()) {
          $requestData['attributes'][] = $user_shift->id;
        }
        
        $user->save();
      }
    }

    $item->update($requestData['values']);    
    
    // update data
    if($item->id) {
      $singular_attributes = ['role', 'org', 'shift'];
      foreach($singular_attributes as $attribute_name) {
        self::removeAttribute($item, $attribute_name);
      }
      
      $item->attr()->sync($requestData['attributes']);
      $item->groups()->sync($requestData['groups']);
      $item->rel()->sync($requestData['relations']);
      
      if($request->getParam("mark_reviewed")) {
        self::setAttribute($item, "submitted_for_review", "on");
        self::setAttribute($item, "reviewed", "on");
        
        if($user) {
          self::notify($user, $item, [
            'title' => 'Character reviewed!',
            'description' => 'Your character have been reviewed.', 
            'icon' => 'exclamation-circle',
            'target' => $this->router->pathFor('participant.character.my'),
          ]);
        }
        
        /* TODO: ACTIVATE EMAIL BUNDLE ON COMPETION
        
        if($user && $user->email) {
          $mail = new Sender($this->container->get('settings'));
          $template = Post::where('slug', 'reviewed-email')->first();
          $email_vars = [
            "BUNDLE_URL" => $this->router->pathFor('participant.bundle', ['hash' => $user->hash]), 
          ];
          $mail->send($user->email, $template->title, $template->content, $email_vars);
          
          $this->flash->addMessage('success', "Character details have been marked as reviewed and the participant have been emailed.");        
        }
        
        */
        $this->flash->addMessage('success', "Character details have been marked as reviewed.");        
      }

      $user = User::where('id', $requestData['values']['user_id'])->first();
      if($user) {
        self::setAttribute($user, "packing_pnqs", $request->getParam("packing_pnqs"));
        self::setAttribute($user, "packing_ta", $request->getParam("packing_ta"));
      }
      
      $this->flash->addMessage('success', "Character details have been saved.");
    } else {
      $this->flash->addMessage('error', "The character could not be saved.");
    }
    
    if( $request->getParam('selfsave') == 1 ) {
      return $response->withRedirect($this->router->pathFor('admin.character.edit', ['uid' => $arguments['uid']]) . "#saved");
    } else {
      return $response->withRedirect($this->router->pathFor('admin.character.list'));
    }
  }
  
  private function characterOrgs() {
    return Attribute::where('name', 'org')->where('value', '<>', '')->get();
  }
  
  private function characterShifts() {
    return Attribute::where('name', 'shift')->where('value', '<>', '')->get();
  }
  
  public function index($request, $response, $arguments)
  {
    $characters = Character::orderBy('name');
    
    //Filter by attribute
    /*
    $characters = Character::whereHas(
        'attr', function ($query) {
            $query->whereIn('name',['NPC','Costume done']);
        }
    )
    ->with('attr');
    */
    
    return $this->view->render($response, 'admin/participants/characters/list.html', [
      'characters' => $characters->get(),
      'debug' => $characters->toSql(),
    ]);
  }
  
  public function submitted_for_review($request, $response, $arguments)
  {
    //Filter by attribute
    $characters = Character::whereHas(
        'attr', function ($query) {
            $query->where([['name', 'submitted_for_review'], ['value', '<>', '0'], ['value', '<>', 'off']]);
        }
    )
    ->with('attr');
    
    return $this->view->render($response, 'admin/participants/characters/list.html', [
      'characters' => $characters->get(),
      'debug' => $characters->toSql(),
    ]);
  }
  
  public function reviewed($request, $response, $arguments)
  {
    //Filter by attribute
    $characters = Character::whereHas(
        'attr', function ($query) {
            $query->where([['name', 'reviewed'], ['value', '<>', '0'], ['value', '<>', 'off']]);
        }
    )
    ->with('attr');
    
    return $this->view->render($response, 'admin/participants/characters/list.html', [
      'characters' => $characters->get(),
      'debug' => $characters->toSql(),
    ]);
  }
  
  public function add($request, $response, $arguments)
  {
    $this->container->view->getEnvironment()->addGlobal('current', [
      'data' => [],
      'attr' => [],
      'new' => true
    ]);
    
    return $this->view->render($response, 'admin/participants/characters/edit.html', self::characterOptions());
  }
  
  public function generate($request, $response, $arguments)
  {
    return $this->view->render($response, 'admin/participants/characters/generate.html', [
      'last' => Character::latest()->first()    
    ]);
  }

  public function postGenerate($request, $response, $arguments)
  {    
    $character_data = [
      "id"            => $request->getParam("id"),
      "org"           => $request->getParam("org"),
      "role"          => $request->getParam("role"),
      "shift"         => $request->getParam("shift"),
      "iso_int_note"  => $request->getParam("iso_int_note"),
      "mil_dem_note"  => $request->getParam("mil_dem_note"),
      "nos_pro_note"  => $request->getParam("nos_pro_note"),
      "lib_col_note"  => $request->getParam("lib_col_note"),
      "log_int_note"  => $request->getParam("log_int_note"),
      "dir_avo_note"  => $request->getParam("dir_avo_note"),
      "phy_non_note"  => $request->getParam("phy_non_note"),
      "mal_con_note"  => $request->getParam("mal_con_note"),
      "iso_int"       => $request->getParam("iso_int"),
      "mil_dem"       => $request->getParam("mil_dem"),
      "nos_pro"       => $request->getParam("nos_pro"),
      "lib_col"       => $request->getParam("lib_col"),
      "log_int"       => $request->getParam("log_int"),
      "dir_avo"       => $request->getParam("dir_avo"),
      "phy_non"       => $request->getParam("phy_non"),
      "mal_con"       => $request->getParam("mal_con"),       
    ];
    
    $character_generations = [];
    foreach ( $character_data["id"] as $i => $id ) {
      $character_generations[$id] = self::saveCharacter($id, [
      "org"           => $character_data["org"][$i],
      "role"          => $character_data["role"][$i],
      "shift"         => $character_data["shift"][$i],
      "iso_int_note"  => $character_data["iso_int_note"][$i],
      "mil_dem_note"  => $character_data["mil_dem_note"][$i],
      "nos_pro_note"  => $character_data["nos_pro_note"][$i],
      "lib_col_note"  => $character_data["lib_col_note"][$i],
      "log_int_note"  => $character_data["log_int_note"][$i],
      "dir_avo_note"  => $character_data["dir_avo_note"][$i],
      "phy_non_note"  => $character_data["phy_non_note"][$i],
      "mal_con_note"  => $character_data["mal_con_note"][$i],
      "iso_int"       => $character_data["iso_int"][$i],
      "mil_dem"       => $character_data["mil_dem"][$i],
      "nos_pro"       => $character_data["nos_pro"][$i],
      "lib_col"       => $character_data["lib_col"][$i],
      "log_int"       => $character_data["log_int"][$i],
      "dir_avo"       => $character_data["dir_avo"][$i],
      "phy_non"       => $character_data["phy_non"][$i],
      "mal_con"       => $character_data["mal_con"][$i],
      ], $arguments);
    }
    
    //die(var_dump($character_generations));
    
    if( !in_array(false, $character_generations, true) ) {
      $this->flash->addMessage('success', count($character_data["id"]) . " Characters created.");      
    }
    
    return $response->withRedirect($this->router->pathFor('admin.character.list'));
  }  
  
  public function post($request, $response, $arguments)
  {    
    return self::save(self::handlePostData($request), $request, $response, $arguments);
  }  

  public function edit($request, $response, $arguments)
  {
    $character = Character::where('id', $arguments['uid'])->first();
    $order = [];
    
    if($character->user_id) {
      $order = Order::where('user_id', $character->user_id)->first();
    }
    
    $this->container->view->getEnvironment()->addGlobal('current', [
      'data' => $character,
      'order' => $order,
      'attr' => self::mapAttributes( $character->attr )
    ]);
    
    return $this->view->render($response, 'admin/participants/characters/edit.html', self::characterOptions());
  }
  
  public function delete($request, $response, $arguments)
  {
    $item = Character::where('id', $arguments['uid'])->first();
    
    $item->attr()->sync([]);
    $item->groups()->sync([]);
    $item->plots()->sync([]);
    $item->rel()->sync([]);

    $item->delete();
    $this->flash->addMessage('warning', "Character {$item->name} was deleted.");
    return $response->withRedirect($this->router->pathFor('admin.character.list'));
  }
  
  public function apiCharacterSearch($request, $response, $arguments) {
    $params = $request->getQueryParams();    
    $res = Character::select('characters.name','characters.id');
      
    foreach($params as $name => $value) {
      $res->whereHas(
        'attr', function ($query) use ($name, $value) {
            if(strpos($value, ':') === false) {
              $comparison = '=';
              $value = ':'.$value;
            } else {
              $comparison = strstr($value, ':', true);
            }
            $value = str_replace(':', '', strstr($value, ':'));          
            $query->where([['name', $name], ['value', $comparison, $value]]);            
          }
      ); //->with('attr');
    }
    
    return $response->withJSON(
        [ 
          "characters" => $res->get(), 
          "filter" => $params, 
          "count" => $res->count(), 
//          "debug" => $res->toSql() 
        ],
        200,
        JSON_UNESCAPED_UNICODE
    );
  }
  
  public function csv($request, $response, $arguments)
  {
    $items = Character::all();

    return $this->view->render($response, 'admin/participants/characters/export.html', [
      'listItems' => $items
    ])->withHeader('Content-Type', 'text/csv');
  }
  
  public function gm($request, $response, $arguments)
  {
    $items = Character::all();

    return $this->view->render($response, 'admin/participants/characters/gm-export.html', [
      'listItems' => $items
    ]);
  }    
}
