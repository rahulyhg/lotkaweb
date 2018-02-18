<?php

namespace App\Controllers\Page\Participant;

use App\Models\Post;
use App\Models\Character;
use App\Models\Attribute;
use App\Models\ItemList;
use App\Models\ListItem;

use App\Controllers\Controller;
use Slim\Views\Twig as View;

class ListPageController extends Controller
{
  private function itemsByType($type, $response, $character = false) {
    return self::render(
      "list", 
      [
        "type" => $type,
        "list" => ListItem::where('type', $type)->get(),
      ], 
      $response
    );    
  }
  
  public function my($request, $response, $arguments){
    $currentCharacter = self::getCurrentOrById($arguments["uid"]);
    $list = $currentCharacter->lists;
    
    return self::render(
      "list-my", 
      [
        "list" => $list ? $lists->first() : [],
      ], 
      $response
    );
  }
  
  
  public function item($request, $response, $arguments){
    $item = ListItem::where('id', $arguments["uid"])->first();
    
    return self::render(
      "list-item", 
      [
        "item" => $item,
      ], 
      $response
    );
  }
  
  public function pnqs($request, $response, $arguments){
    return self::itemsByType('pnqs', $response);
  }
  
  public function ta($request, $response, $arguments){
    return self::itemsByType('ta', $response);
  }
  
  public function save($request, $response, $arguments){
    return "TODO : save";
  }
}