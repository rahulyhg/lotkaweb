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

class RelationPageController extends Controller
{
  public function index($request, $response, $arguments){
    return self::render(
      "relations-list", 
      [
        "relations" => self::getRalationsInfo()
      ], 
      $response
    );
  }
  
  public function relation($request, $response, $arguments) {
    $relationship = Relation::where('id', $arguments['uid'])->first();  
    $current = self::getCurrentUser();
    
    $currentCharacter = $current["character"];
    $currentUser = $current["user"];
    
    $inParty = self::partOfRelationship($relationship, $currentCharacter["data"]);
    $isPublic = count($relationship->attr->where('name','public')->whereIn('value',['true','1'])->all()) > 0;
    
    if(!$inParty && !$isPublic) {      
      $this->flash->addMessage('error', "You can't view this relationship.");
      return $response->withRedirect($this->router->pathFor('participant.home'));
    }
    
    self::markNotificationsAsSeen($relationship, $currentUser);
    
    $characters = [];
    if($inParty) {
      $characters = self::getCharacers($relationship);        
    }
    
    return self::render(
      "relation", 
      [
        "relation" => $relationship,
        "canEdit" => $inParty,
        "types" => Attribute::where('name', 'relationship_type')->get(),
        "characters" => $characters,
        "uid" => $arguments['uid'],
      ], 
      $response
    );
  }
  
  public function my($request, $response, $arguments){
    $current = $this->container->auth->isWriter() && $arguments["uid"] ?
      ["character" => self::getCharacter($arguments["uid"])] : self::getCurrentUser();

    $currentCharacter = $current["character"];
    
    return self::render(
      "relations-list", 
      [
        "relations" => self::getRalationsInfo($currentCharacter["data"]->id),
      ], 
      $response
    );
  }
  
  public function pending($request, $response, $arguments){
    return self::render(
      "relations-pending", 
      [
        "relations" => [], #self::getPendingRalationsInfo()
      ], 
      $response
    );
  }
  
  public function add($request, $response, $arguments){
    return "TODO : add";
  }
  
  public function edit($request, $response, $arguments){
    $relationship = Relation::where('id', $arguments['uid'])->first();
    $currentCharacter = self::getCurrentUser()["character"];
    $inParty = self::partOfRelationship($relationship, $currentCharacter["data"]);
    
    if(!self::partOfRelationship($relationship, $currentCharacter["data"])) {
      $this->flash->addMessage('error', "You can't edit this relationship.");
      return $response->withRedirect($this->router->pathFor('participant.home'));
    }
    
    $relation_attributes = [
      'public' =>                 !!$request->getParam('public'),
      'relationship_type' =>        $request->getParam('relationship_type'),
      'source' =>                   $request->getParam('source'),
      'target' =>                   $request->getParam('target'),
      'relationship_icon' =>        $request->getParam('relationship_icon'),
    ];
    
    foreach($relation_attributes as $key => $value) {
      $value = is_null($value) ? false : $value;
      $debug[] = self::setAttribute($relationship, $key, $value);
    }
    
    $relationship->name = $request->getParam('name');
    $relationship->description = $request->getParam('description');
    
    $characters = $request->getParam('character_ids');
    $characters = is_array($characters) ? $characters : [$characters];
    $relationship->characters()->sync($characters);
    
    foreach($characters as $character_id) {
      if( $currentCharacter->id != $character_id) {
        $notification_data = [
          'title' => 'New ' . $relation_attributes['relationship_type'] . '!',
          'description' => 'You have been added to a relationship.', 
          'icon' => 'chain',
        ];

        $char = Character::where('id', $character_id)->first();
        if ($inParty) {
          $notification_data['title'] = '"' . $relation_attributes['relationship_type'] . '" updated!';
          $notification_data['description'] = $currentCharacter['data']['name'] . ' made some changes.';
        }
        
        self::notify($char->user, $relationship, $notification_data);
      }
    }    
    
    if($relationship->save()) {
      $this->flash->addMessage('success', "Relationship updated."); 
    } else {
      $this->flash->addMessage('error', "Something went wrong saving the relationship, sorry."); 
    }
    
    return $response->withRedirect(
      $this->router->pathFor('participant.relation', ['uid' => $arguments["uid"]])
    );
  }
  
  public function delete($request, $response, $arguments){
    return "TODO : delete";
  }
  
  public function join($request, $response, $arguments){
    return "TODO : join";
  }
  
  public function accept($request, $response, $arguments){
    return "TODO : accept";
  }
  
  public function reject($request, $response, $arguments){
    return "TODO : reject";
  }
  
  public function post($request, $response, $arguments){
    return "TODO : save";
  }
  
  private function partOfRelationship($relationship, $character = false) {
    if($this->container->auth->isWriter()) return true;
    
    foreach($relationship->characters as $rel_char) {
      if($rel_char->id == $character->id) return true;
    }

    foreach($relationship->attr->where('name', 'source')->all() as $rel_char) {
      if($rel_char->value == $character->id) return true;
    }

    foreach($relationship->attr->where('name', 'target')->all() as $rel_char) {
      if($rel_char->value == $character->id) return true;
    }
    
    foreach($relationship->groups() as $rel_group) {
      foreach($rel_group->characters as $rel_char) {
        if($rel_char->id == $character->id) return true;
      }
    }
    
    return false;
  }
  
  private function isNpc($character) {
    return !is_null( $character->attr->where('name','npc')->where('value','on')->first() );
  }
  
  private function getCharacers($relationship) {
    $characters = Character::where('name', '<>', '')->orderBy('name')->get();
    
    $character_list = [];
    foreach ($characters as $character) {
      if(
          !self::isNpc($character) && 
          !$relationship->characters->where('id', $character->id)->first()
      ) {
        $character_list[] = $character;
      }
    }
        
    return $character_list;
  }
  
  private function getRalationsInfo($character_id = 0) {
    if($character_id > 0) {
      $relationships = Character::where('id', $character_id)->first()->rel;
    } else {
      $relationships = Relation::whereHas(
          'attr', function ($query) {
            $query
              ->where([['name', 'public'], ['value', '1']])
              ->orWhere([['name', 'public'], ['value', 'true']]);
          }
        )->with('attr')->get();
    }
    
    return $relationships;
  }
}