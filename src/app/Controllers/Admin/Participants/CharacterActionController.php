<?php

namespace App\Controllers\Admin\Participants;

use App\Models\Character;
use App\Models\Attribute;
use App\Models\Group;
use App\Models\User;
use App\Models\Relation;
use App\Models\Plot;
use App\Controllers\Controller;
use Respect\Validation\Validator as v;
use Slim\Views\Twig as View;

class CharacterActionController extends Controller
{

  private function character_attributes() {
    return [
      'quote', 'org', 'shift', 'role', 'traits', 'nickname', 'synopsis',
      'iso_int_note','mil_dem_note','nos_pro_note','lib_col_note','log_int_note','dir_avo_note','phy_non_note','mal_con_note',
       'iso_int','mil_dem','nos_pro','lib_col','log_int','dir_avo','phy_non','mal_con',
      'self_vision', 'others_vision', 'notes', 'bunk_budy', 'age',
      'gender',
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
      'set_attr' => self::character_attributes(),
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
    
    foreach (self::character_attributes() as $attr) {
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
      $attribute_ids[] = Attribute::create([
        'name' => 'org', 
        'value' => $request->getParam('new_org')
      ])->id;
    }
    
    if($request->getParam('new_shift')) {
      $attribute_ids[] = Attribute::create([
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
  
  private function save($requestData, $request, $response, $arguments) {
    // update data
    $item = Character::firstOrCreate(['id' => $arguments['uid']]);
    $item->update($requestData['values']);
    
    // update data
    if($item->id) {
      $item->attr()->sync($requestData['attributes']);
      $item->groups()->sync($requestData['groups']);
      $item->rel()->sync($requestData['relations']);
      
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
    
    $this->container->view->getEnvironment()->addGlobal('current', [
      'data' => $character,
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
}