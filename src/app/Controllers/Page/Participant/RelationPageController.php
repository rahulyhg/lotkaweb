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
        "relations" => [], #self::getRalationsInfo()
      ], 
      $response
    );
  }
  
  public function my($request, $response, $arguments){
    return self::render(
      "relations-my", 
      [
        "relations" => [], #self::getRalationsInfo($arguments["uid"])
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
    
    if(!self::partOfRelationship($relationship, $currentCharacter["data"])) {
      $this->flash->addMessage('error', "You can't edit this relationship.");
      return $response->withRedirect($this->router->pathFor('participant.home'));
    }
    
    echo "can edit";
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
    
    foreach($relationship->characters() as $rel_char) {
      if($rel_char->id == $character->id) return true;
    }

    foreach($relationship->attr->where('name', 'source')->all() as $rel_char) {
      if($rel_char->value == $character->id) return true;
    }

    foreach($relationship->attr->where('name', 'target')->all() as $rel_char) {
      if($rel_char->value == $character->id) return true;
    }
    
    foreach($relationship->groups() as $rel_group) {
      foreach($rel_group->characters() as $rel_char) {
        if($rel_char->id == $character->id) return true;
      }
    }
    
    die();
    
    return false;
  }
}