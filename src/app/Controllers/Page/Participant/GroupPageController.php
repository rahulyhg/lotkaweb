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

class GroupPageController extends Controller
{
  public function index($request, $response, $arguments){
    return self::render(
      "group-list", 
      [
        "groups" => self::getGroupsInfo(),
      ], 
      $response
    );
  }
  
  public function my($request, $response, $arguments){
    return self::render(
      "groups-my", 
      [
        "groups" => self::getUsersGroups($this->container->sentinel->getUser()),
      ], 
      $response
    );
  }
  
  public function group($request, $response, $arguments){
    return self::render(
      "group", 
      [
        "group" => self::getGroupInfo($arguments["uid"]),
      ], 
      $response
    );
  }  
  
  public function add($request, $response, $arguments){
    return "TODO : add";
  }
  
  public function edit($request, $response, $arguments){
    return "TODO : edit";
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
  
  private function getUsersGroups($user) {
    $groups = $user->groups();
    
    $group_list = [];
    foreach ($groups as $group) {
      $group_list[] = [
        "data" =>$group,
        "attributes" => self::mapAttributes( $group->attr ),
      ];
    }
        
    return $group_list;
  }  
  
  private function getGroupsInfo() {
    $groups = Group::visible()->get();
    
    #die(var_dump($groups->toSql()));
    
    $group_list = [];
    foreach ($groups as $group) {
      $group_list[] = [
        "data" =>$group,
        "attributes" => self::mapAttributes( $group->attr ),
      ];
    }
        
    return $group_list;
  }
  
  private function getGroupInfo($name) {
    $group = Group::where('name', $name)->first();
    
    return $group ? [
      "data" => $group,
      "attributes" => self::mapAttributes( $group->attr ),
      "characters" => self:getGroupCharacters( $group ),
    ] : [];    
  }
  
  private function getGroupPlayers($group) {
    $groupPlayers = $group->users();
    
    $playerCollection = [];
    for($groupPlayers as $player) {
      $playerCollection[] = self::getPlayerInfo($player->id);
    }
    return $playerCollection;    
  }  
}