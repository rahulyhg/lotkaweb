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
    
    //die(var_dump($character->id));
    
    return self::render(
      "character-my", 
      [
        "character" => $character ? self::getCharacterInfo($character->id) : [],
      ], 
      $response
    );
  }  
  
  public function character($request, $response, $arguments){
    return self::render(
      "character", 
      [
        "character" => self::getCharacterInfo($arguments["uid"]),
      ], 
      $response
    );
  }
  
  private function getCharacersInfo($AttributeFilter = [['name','like','%']]) {
    $characters = Character::whereHas(
        'attr', function ($query) use ($AttributeFilter) {
            $query->where( $AttributeFilter);
        }
    )->where('name', '<>', '')->with('attr')->get();
    
    $character_list = [];    
    foreach ($characters as $character) {
      $character_list[] = [
        "data" => $character, 
        "attributes" => self::mapAttributes($character->attr),
      ];
    }
        
    return $character_list;
  }
  
  private function getCharacterInfo($uid) {
    $character = Character::where('id', $uid)->first();
    return $character ? [
        "data" => $character, 
        "attributes" => self::mapAttributes($character->attr),
      ] : [];
  } 
}