<?php

namespace App\Controllers\Page\Participant;

use App\Models\Character;
use App\Models\Plot;
use App\Models\Group;
use App\Models\Relation;
use App\Models\Task;

use App\Controllers\Controller;
use App\Controllers\Admin\Participants\CharacterActionController as CharacterActions;

use Slim\Views\Twig as View;

class CharacterPageController extends Controller
{
  public function index($request, $response, $arguments){
    return self::render(
      "character-list", 
      [
        "characters" => self::getCharacersInfo(),
      ], 
      $response
    );
  }
  
  public function gallery($request, $response, $arguments){
    return self::render(
      "character-gallery", 
      [
        "characters" => self::getCharacersInfo(),
      ], 
      $response
    );
  }
  
  public function my($request, $response, $arguments){
    $user = self::getCurrentUser();
    $character = $user["user"]->character;
    
    return self::render(
      "character-my", 
      [
        "character" => $character ? self::getCharacterInfo($character->id) : [],
        "current" => $user
      ], 
      $response
    );
  }  
  
  public function character($request, $response, $arguments){
    return self::render(
      "character", 
      [
        "character" => self::getCharacterInfo($arguments["uid"]),
        "postClass" => "mt-10",
        "mainClass" => "mt-0 pt-10"
      ], 
      $response
    );
  }
  
  public function save($request, $response, $arguments){
    return "TODO : save";
  }
  
  //===========================================================================
  // Helpers
  //===========================================================================  
  
  private function isNpc($character) {
    return !is_null( $character->attr->where('name','npc')->where('value','on')->first() );
  }
  
  private function getCharacersInfo($AttributeFilter = [['name','like','%']]) {
    $characters = Character::whereHas(
        'attr', function ($query) use ($AttributeFilter) {
            $query->where( $AttributeFilter );
        }
    )->where('name', '<>', '')->with('attr')->get();
    
    $character_list = [];
    foreach ($characters as $character) {
      if(!self::isNpc($character)) {
        $character_list[] = [
          "data" => $character, 
          "attributes" => self::mapAttributes($character->attr),
        ];
      }
    }
        
    return $character_list;
  }
  
  private function getCharacterInfo($uid) {
    $character = Character::where('id', $uid)->first();    
    return $character && !self::isNpc($character) ? [
        "data" => $character, 
        "attributes" => self::mapAttributes($character->attr),
        "player" => self::getPlayerInfo($character->user->id),
      ] : [];
  } 
}