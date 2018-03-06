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
        "relations" => self::getRelationsInfo()
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
    
    $inParty = self::partOfRelationship($relationship, $currentCharacter["data"]) || $relationship_id == 'new';
    $isOpen = self::is($relationship, 'open');
    
    $isPublic = self::is($relationship, 'public');
    $isRequest = $isPublic && $isOpen;
    
    self::markNotificationsAsSeen($relationship, $currentUser);
    
    if(!$relationship || (!$inParty && (!$isOpen && !$isPublic))) {      
      $this->flash->addMessage('error', "We can't find this relationship, either it's been removed or you can't access it any more.");
      return $response->withRedirect($this->router->pathFor('participant.home'));
    }

    $characters = [];
    if($inParty) {
      $characters = self::getCharacers($relationship, $isRequest);        
    }
    
    return self::render(
      "relation", 
      [
        "relation" => $relationship,
        "canEdit" => $inParty,
        "types" => Attribute::where('name', 'relationship_type')->orderBy('value')->get(),
        "characters" => $characters,
        "uid" => $relationship_id,
        "isRequest" => $isRequest,
        "isOpen" => $isOpen, 
        "currentCharacter" => $currentCharacter["data"],
      ], 
      $response
    );
  }
  
  public function my($request, $response, $arguments){
    $uid = isset($arguments["uid"]) ? $arguments["uid"] : false;
    $currentCharacter = self::getCurrentOrById($uid);
    
    return self::render(
      "relations-list", 
      [
        "relations" => self::getRelationsInfo($currentCharacter["data"]->id),
      ], 
      $response
    );
  }
  
  public function pending($request, $response, $arguments){
    $uid = isset($arguments["uid"]) ? $arguments["uid"] : false;
    $currentCharacter = self::getCurrentOrById($uid);
    
    return self::render(
      "relations-list", 
      [
        "tools" => ["pending" => true], 
        "relations" => self::pendingRelationships($currentCharacter["data"]),
      ], 
      $response
    );
  }
  
  public function publicRequests($request, $response, $arguments){
    return self::render(
      "relations-list", 
      [
        "title" => "Publicly Requested Relationships",
        "relations" => self::getRelationsRequests(),
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
    
    $inParty = self::partOfRelationship($relationship, $currentCharacter["data"]) || $arguments['uid'] == 'new';    
    $isOpen = self::is($relationship, 'open');
    $isPublic = self::is($relationship, 'public');
    $isRequest = $isPublic && $isOpen && !$inParty;

    if(!$relationship || (!$inParty && !$isOpen)) {
      $this->flash->addMessage('error', "You can't edit this relationship. ($relationship->id)");
      return $response->withRedirect($this->router->pathFor('participant.home'));
    }
    
    $characters = $request->getParam('character_ids');
    $characters = is_array($characters) ? $characters : [$characters];
    
    if($inParty) {
      $relation_attributes = [
        'open' =>                   !!$request->getParam('open'),
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
    } else if($isRequest) {
      $characters = [ $currentCharacter["data"]->id];
    }

    if($characters == [null]) {
      $relationship->characters()->sync([]);
      $relationship->attr()->sync([]);
      $relationship->groups()->sync([]);
      $relationship->notifications()->sync([]);
      $relationship->delete();
      $this->flash->addMessage('error', "Relationship removed.");

      return $response->withRedirect($this->router->pathFor('participant.home'));
    }

    foreach($characters as $character_id) {
      if( $currentCharacter['data']->id != $character_id) {
        $char = Character::where('id', $character_id)->first();
        if(!$char->user) continue;
        
        $notification_present = false;
        $char_user_notifications = $char->user->notifications()->where('seen_at', null)->get();
        foreach($char_user_notifications as $existing_notification) {
          if($existing_notification->relations()->where('id', $relationship->id)->get()) {
            $notification_present = true;
            break;          
          }
        }
        if($notification_present) continue;
        
        $notification_data = [];
        if (
            $relationship->characters()->where('character_id', $character_id)->get() 
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
    
    
    $relationship->characters()->sync($characters, $inParty);    
    
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
    if($attr_pending) {
      $relationship->attr()->detach($attr_pending->id);
    } else {
      $this->flash->addMessage('error', "This relationship is no longer pending.");
      return $this->router->pathFor('participant.relation.pending');      
    }
        
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
    
    return $this->router->pathFor('participant.relation.pending'); //, ['uid' => $uid]);
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
  
  private function partOfRelationship($model, $character = false) {
    return (
      $this->container->auth->isWriter() || ($character && (
        $model->attr()->whereIn('name', ['source', 'target'])->where('value', $character->id)->get()->count() ||
        $model->characters()->where('character_id', $character->id)->get()->count() ||
        self::partOfGroups($model->groups(), $character->id) )
      )
    ) ? true : false;
  }
  
  private function partOfGroups($groups, $character_id) {
    foreach($groups as $group) {
      if($group->characters()->where('character.id', $character_id)->get()->count()) return true;
    }
    return false;
  }
  
  private function getCharacers($relationship, $includeCurrent) {
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
  
  private function getRelationsInfo($character_id = 0) {
    if($character_id > 0) {
      $relationships = Character::where('id', $character_id)
        ->first()->rel; //->where('name', '<>', '')->all();
    } else {
      $relationships = Relation::whereHas(
        'attr', function ($query) {
          $query->where('name', 'public')->whereIn('value', ['true','1','on']);
        }
      )->whereDoesntHave(
          'attr', function ($query) { $query->where('name', 'pending'); }
      )->with('attr')
      ->withCount('characters')
      ->having('characters_count', '>', 1)
      ->get();
    }
    
    return $relationships;
  }

  private function getRelationsRequests() {
    $relationships = Relation::whereHas(
        'attr', function ($query) {
          $query->where('name', 'open')->whereIn('value', ['true','1','on']);
        }
      )->whereHas(
        'attr', function ($query) {
          $query->where('name', 'public')->whereIn('value', ['true','1','on']);
        }
      )->with('attr');

    return $relationships->get();
  }

  private function pendingRelationships($character) {
    $pending_relationships = [];
    foreach($character->rel as $relation) {
      if($relation->attr()->where([['name', 'pending'], ['value',$character->id]])->first()) {
        $pending_relationships[] = $relation;
      }
    }
    
    return $pending_relationships;
  }
}