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
    return self::render(
      "character-my", 
      [
        "character" => self::getCharacterInfo($user["user"]->id),
      ], 
      $response
    );
  }  
  
  public function character($request, $response, $arguments){
    return self::render(
      "character", 
      [
        "character" => self::getCharacterInfo(),
      ], 
      $response
    );
  }
  
  private function getCharacersInfo() {
    $characters = Character::whereHas(
        'attr', function ($query) {
            $query->whereIn('name', 
                            ['NPC','Costume done']);
        }
    )->with('attr');
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