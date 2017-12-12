<?php

namespace App\Controllers\Page\Participant;

use App\Models\Character;
use App\Models\Plot;
use App\Models\Group;
use App\Models\Relation;
use App\Models\Task;

use App\Controllers\Controller;

use Slim\Views\Twig as View;

class SchedulePageController extends Controller
{
  public function index($request, $response, $arguments){
    return self::render(
      "schedules", 
      [
        "schedules" => [], #self::getCharacersInfo()
      ], 
      $response
    );
  }
  
  public function my($request, $response, $arguments){
    $user = self::getCurrentUser();
    return self::render(
      "schedule-my", 
      [
        "schedule" => [], #self::getCharacterInfo($user["user"]->id)
      ], 
      $response
    );
  }  
}