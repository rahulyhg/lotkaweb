<?php

namespace App\Controllers\Page\Participant;

use App\Models\Character;
use App\Models\Plot;
use App\Models\Group;
use App\Models\Relation;
use App\Models\Task;

use App\Controllers\Controller;
use Slim\Views\Twig as View;

class PlayersPageController extends Controller
{
  public function index($request, $response, $arguments){
    return self::render(
      "player-list", 
      [
        "players" => self::getPlayersInfo()
      ], 
      $response
    );
  }
  
  public function gallery($request, $response, $arguments){
    return self::render(
      "player-gallery", 
      [
        "players" => self::getPlayersInfo()
      ], 
      $response
    );
  }
  
  public function player($request, $response, $arguments){
    return self::render(
      "player", 
      [
        "player" => self::getPlayerInfo($arguments["uid"])
      ], 
      $response
    );
  }
  
  private function getPlayersInfo() {
    $role = $this->container->sentinel->findRoleBySlug('participant');
    $users = $role->users()->orderBy('displayname', 'ASC')->get();
    $user_list = [];
    
    foreach ($users as $user) {        
      $user_list[] = self::getPlayerInfo($user->id);
    }
        
    return $user_list;
  }
}