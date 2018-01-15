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

class PlotPageController extends Controller
{
  public function index($request, $response, $arguments){
    return self::render(
      "plot-list", 
      [
        "plots" => [], #self::getPlotsInfo()
      ], 
      $response
    );
  }
  
  public function my($request, $response, $arguments){
    return self::render(
      "plot-my", 
      [
        "plots" => [], #self::getPlotsInfo($arguments["uid"])
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
}