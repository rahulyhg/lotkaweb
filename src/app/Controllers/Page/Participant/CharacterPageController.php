<?php

namespace App\Controllers\Page\Participant;

use App\Models\Character;
use App\Models\Plot;
use App\Models\Group;
use App\Models\Relation;
use App\Models\Task;

use App\Controllers\Controller;
use App\Controllers\Admin\Participants\CharacterActionController as CharacterActions;
use App\Controllers\Page\Participant\OnboardingPageController as OnboardingActions;
  
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
    $player = $this->container->auth->isWriter() && $arguments["uid"] ?
      self::getPlayerInfo($arguments["uid"]) : self::getCurrentUser();
    
    $character = $player["user"]->character;    
    $user = $player["user"];
    
    return self::render(
      "character-my", 
      [
        "character" => $character ? self::getCharacterInfo($character->id) : [],
        "current" => $user,
        "genders" => ['Non-binary','Female','Male','Other'],
        "uid" => isset($arguments["uid"]) ? $arguments["uid"] : false,
      ], 
      $response
    );
  }  
  
  public function character($request, $response, $arguments){
    return self::render(
      "character", 
      [
        "character" => self::getCharacterInfo($arguments["uid"], true),
        "postClass" => "mt-10",
        "mainClass" => "mt-0 pt-10"
      ], 
      $response
    );
  }
  
  public function save($request, $response, $arguments){
    $player = $this->container->auth->isWriter() && isset($arguments["uid"]) ?
      self::getPlayerInfo($arguments["uid"]) : self::getCurrentUser();
    
    $character = $player["user"]->character;
    $user = $player["user"];
    
    #$character
    $character_attributes = [
      'nickname' =>                 $request->getParam('nickname'),
      'gender' =>                   $request->getParam('gender'),
      'pronoun' =>                  $request->getParam('pronoun'),
      'age' =>                      $request->getParam('age'),
      'synopsis' =>                 $request->getParam('synopsis'),
      'history' =>                  $request->getParam('history'),
      'traumas' =>                  $request->getParam('traumas'),
      'contacts_in_haven' =>        $request->getParam('contacts_in_haven'),
      'personnel_file' =>           $request->getParam('personnel_file'),
      'personal_property_items' =>  $request->getParam('personal_property_items'),
      'haven_id' =>                 $request->getParam('haven_id'),
      'how_survived' =>             $request->getParam('how_survived'),
      'submitted_for_review' =>     $request->getParam('submitted_for_review'),
    ];
      
    #$user
    $user_attributes = [
      'pref_romance' =>                 !!$request->getParam('pref_romance'),
      'pref_fall_from_grace' =>         !!$request->getParam('pref_fall_from_grace'),
      'pref_shared_trauma' =>           !!$request->getParam('pref_shared_trauma'),
      'pref_shared_secret' =>           !!$request->getParam('pref_shared_secret'),
      'pref_everyday' =>                !!$request->getParam('pref_everyday'),
      'pref_counselling' =>             !!$request->getParam('pref_counselling'),
      'pref_conflict' =>                !!$request->getParam('pref_conflict'),
      'pref_conflict_ideological' =>    !!$request->getParam('pref_conflict_ideological'),
      'pref_conflict_intrapersonal' =>  !!$request->getParam('pref_conflict_intrapersonal'),
      'pref_friendships' =>             !!$request->getParam('pref_friendships'),
      'pref_social_climb' =>            !!$request->getParam('pref_social_climb'),
      'pref_enemies' =>                 !!$request->getParam('pref_enemies'),
      'pref_player_def_1' =>            $request->getParam('pref_player_def_1'),
      'pref_player_def_2' =>            $request->getParam('pref_player_def_2'),
      'pref_player_def_3' =>            $request->getParam('pref_player_def_3'),
    ];
    
    
    $debug = [];
    # Saving User Attributes
    foreach($user_attributes as $key => $value) {
      $value = is_null($value) ? false : $value;
      $debug[] = [self::setAttribute($user, $key, $value), $key, $value] ;
    }
    
    # Saving Character Attributes
    foreach($character_attributes as $key => $value) {
      $value = is_null($value) ? false : $value;
      self::setAttribute($character, $key, $value);
    }

    $this->flash->addMessage('debug', [$user->displayname, self::mapAttributes($user->fresh()->attr)]);
    
    # Check if we have updated data
    $hasUpload = $request->getUploadedFiles();
    if( isset($hasUpload['portrait']) && strlen($hasUpload['portrait']->file) ) {
      $file_name = OnboardingActions::uploadFile($hasUpload, $user, $this->container->get('settings')['user_images'], true);
      self::setAttribute(
        $user, 
        'portrait', 
        $file_name
      );
    }    
    
    return $response->withRedirect(
      isset($arguments["uid"])  ? 
      $this->router->pathFor('participant.character.my.admin', ['uid' => $arguments["uid"]]):
      $this->router->pathFor('participant.character.my')
    );
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
  
  private function getCharacterInfo($uid, $filterNPC = false) {
    $character = Character::where('id', $uid)->first();
    
    return $character && !($filterNPC && self::isNpc($character)) ? [
        "data" => $character, 
        "attributes" => self::mapAttributes($character->attr),
        "player" => self::getPlayerInfo($character->user_id),
      ] : [];
  } 
}
