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
    $relationship_id = preg_replace("/[^(new|\d*)]/", "", $arguments['uid']);
    $relationship = $relationship_id != 'new' ? 
      Relation::where('id', $relationship_id)->first() :
      new Relation(['name' => '']);
    
    $current = self::getCurrentUser();
    
    $currentCharacter = $current["character"];
    $currentUser = $current["user"];
    
    $inParty = self::partOfRelationship($relationship, $currentCharacter["data"]);
    $isPublic = !!$relationship->attr->where('name','public')->whereIn('value',['true','1'])->all();
    
    self::markNotificationsAsSeen($relationship, $currentUser);
    
    if(!$relationship || (!$inParty && !$isPublic && $relationship_id != 'new')) {      
      $this->flash->addMessage('error', "You can't view this relationship.");
      return $response->withRedirect($this->router->pathFor('participant.home'));
    }
            
    if($relationship_id == 'new') {
      $inParty = true;
    }

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
        "uid" => $relationship_id,
        "currentCharacter" => $currentCharacter["data"],
      ], 
      $response
    );
  }
  
  public function my($request, $response, $arguments){
    $currentCharacter = self::getCurrentOrById($arguments["uid"]);
    
    return self::render(
      "relations-list", 
      [
        "relations" => self::getRalationsInfo($currentCharacter["data"]->id),
      ], 
      $response
    );
  }
  
  public function pending($request, $response, $arguments){
    $currentCharacter = self::getCurrentOrById($arguments["uid"]);
    
    return self::render(
      "relations-list", 
      [
        "tools" => ["pending" => true], 
        "relations" => self::pendingRelationships($currentCharacter["data"]),
      ], 
      $response
    );
  }
  
  public function add($request, $response, $arguments){
    return "TODO : add";
  }
  
  public function edit($request, $response, $arguments){
    $relationship = $arguments['uid'] != 'new' ? 
      Relation::where('id', $arguments['uid'])->first() : 
      Relation::create(['name' => '']);
    $currentCharacter = self::getCurrentUser()["character"];
    $inParty = self::partOfRelationship($relationship, $currentCharacter["data"]);
    
    if(!$inParty && count($relationship->characters) > 0) {
      $this->flash->addMessage('error', "You can't edit this relationship. ($relationship->id)");
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
    
    foreach($characters as $character_id) {
      if( $currentCharacter['data']->id != $character_id) {
        $notification_data = [];

        $char = Character::where('id', $character_id)->first();
        if (
            $relationship->characters->where('id', $character_id)->all() 
//            || self::partOfRelationship($relationship, $char) //Only check other character containers if not directly attached
          ) {
          $notification_data = [
            'title' => $relation_attributes['relationship_type'] . ' updated!',
            'description' => $currentCharacter['data']->name . ' updated "' . $request->getParam('name') . '".',
            'icon' => 'eye',
          ];
        } else {
          $notification_data = [
            'title' => 'New ' . $relation_attributes['relationship_type'] . '!',
            'description' => 'You have been added to a relationship.', 
            'icon' => 'chain',
            'target' => $this->router->pathFor('participant.relation.pending') . '#' . $relationship->id,
          ];
          
          $relationship->attr()->attach(Attribute::firstOrCreate([
            'name' => 'pending',
            'value' => $char->id,
          ])->id);
        }
        
        self::notify($char->user, $relationship, $notification_data);
      }
    }
    
    $relationship->characters()->sync($characters);    
    
    if($relationship->save()) {
      $this->flash->addMessage('success', "Relationship updated."); 
    } else {
      $this->flash->addMessage('error', "Something went wrong saving the relationship, sorry."); 
    }
    
    return $response->withRedirect(
      $this->router->pathFor('participant.relation', ['uid' => $relationship->id])
    );
  }
  
  private function handleRelation($uid, $acceptRejct) {
    $currentCharacter = self::getCurrentUser()["character"];
    $relationship = $currentCharacter["data"]->rel()->where('relation_id', $uid)->first();
    
    if(!$relationship) {
      $this->flash->addMessage('error', "You don't seem to be part of this relationship any more.");
      return $this->router->pathFor('participant.relation.pending');
    }
        
    $attr_pending = $relationship->attr()->where([['name', 'pending'], ['value',$currentCharacter["data"]->id]])->first();
    if($attr_pending) $relationship->attr()->detach($attr_pending->id);
        
    if($acceptRejct == 'accept') {      
      $this->flash->addMessage('success', "Relationship accepted.");

      foreach($relationship->characters as $character) {
        if( $currentCharacter->id != $character->id) {
          $notification_data = [
            'title' => $character->name . ' accepted ' . $relation_attributes['relationship_type'] . '!',
            'icon' => 'chain',
          ];
          self::notify($character->user, $relationship, $notification_data);
        }
      }
    }
    if($acceptRejct == 'reject') {
      $relationship->characters()->detach($currentCharacter["data"]->id);      
      $this->flash->addMessage('error', "Relationship rejected.");
      return $this->router->pathFor('participant.relation.pending');
    }
    
    return $this->router->pathFor('participant.relation', ['uid' => $uid]);
  }
  
  public function accept($request, $response, $arguments){
    return $response->withRedirect(
      self::handleRelation($arguments["uid"], 'accept')
    );
  }
  
  public function reject($request, $response, $arguments){
    return $response->withRedirect(
      self::handleRelation($arguments["uid"], 'reject')
    );
  }
  
  public function delete($request, $response, $arguments){
    return "TODO : delete";
  }
  
  public function join($request, $response, $arguments){
    return "TODO : join";
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
      $relationships = Character::where('id', $character_id)
        ->first()->rel; //->where('name', '<>', '')->all();
    } else {
      $relationships = Relation::whereHas( //where('name', '<>', '')
          'attr', function ($query) {
            $query->where([['name', 'public'], ['value', '1']])
              ->orWhere([['name', 'public'], ['value', 'true']]);        
          }
        )->whereDoesntHave(
          'attr', function ($query) {
            $query->where('name', 'pending');
          }
        )->with('attr')->get();
    }
    
    return $relationships;
  }

  private function pendingRelationships($character) {
    $pending_relationships = [];
    foreach($character->rel as $relation) {
      if($relation->attr->where([['name', 'pending'],['value',$character->id]])->all()) {
        $pending_relationships[] = $relation;
      }
    }
    
    return $pending_relationships;
  }
}