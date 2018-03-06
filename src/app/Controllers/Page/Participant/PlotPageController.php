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
        "plots" => self::getPlotsInfo(),
      ], 
      $response
    );
  }
  
  public function my($request, $response, $arguments){
    $uid = isset($arguments["uid"]) ? $arguments["uid"] : false;
    $currentCharacter = self::getCurrentOrById($uid);
    
    return self::render(
      "plot-list", 
      [
        "plots" => self::getPlotsInfo($currentCharacter["data"]->id),
      ], 
      $response
    );
  }
  
  public function pending($request, $response, $arguments){
    $uid = isset($arguments["uid"]) ? $arguments["uid"] : false;
    $currentCharacter = self::getCurrentOrById($uid);
    
    return self::render(
      "plot-list", 
      [
        "tools" => ["pending" => true], 
        "relations" => self::pendingPlots($currentCharacter["data"]),
      ], 
      $response
    );
  }  
  
  public function plot($request, $response, $arguments){
    $plot_id = preg_replace("/[^(new|\d*)]/", "", $arguments['uid']);
    $plot = $plot_id != 'new' ? 
      Plot::where('id', $plot_id)->first() :
      new Plot(['name' => '']);
    
    $current = self::getCurrentUser();
    
    $currentCharacter = $current["character"];
    $currentUser = $current["user"];
    
    $inPlot = self::partOfPlot($plot, $currentCharacter["data"]);
    $isPublic = !!$plot->attr->where('name','public')->whereIn('value',['true','1'])->all();
    $isLocked = !!$plot->attr->where('name','reviewed')->whereIn('value',['true','1'])->all();    
    $isRequest = !$isLocked && $isPublic;
    
    self::markNotificationsAsSeen($plot, $currentUser);
        
    if( !$plot || (!$isRequest && (!$inPlot && $plot_id != 'new'))) {      
      $this->flash->addMessage('error', "We can't find this plot, either it's been removed or you can't access it any more.");
      return $response->withRedirect($this->router->pathFor('participant.home'));
    }

    $characters = [];
    $parents = [];
    if(!$isLocked) {
      if($plot_id == 'new') {
        $inPlot = true;
      } else {
        $isRequest = $isPublic;
        $inPlot = $inPlot ? $inPlot : $isRequest;
      }
      
      if($inPlot) {
        $characters = self::getCharacers($plot, $isRequest);
        $parents = [
          "public" => self::getPlotsInfo(), 
          "character" => self::getPlotsInfo($currentCharacter["data"]->id), 
        ];
      }
    }
    
    return self::render(
      "plot", 
      [
        "plot" => $plot,
        "canEdit" => $isLocked ? false : $inPlot,
        "inPlot" => $inPlot, 
        "characters" => $characters,
        "uid" => $plot_id,
        "isRequest" => $isLocked ? false : $isRequest,
        "currentCharacter" => $currentCharacter["data"],
        "parents" => $parents
      ], 
      $response
    );
  }
  
  public function requests($request, $response, $arguments){
    return "TODO : requests";
  }
  
  public function save($request, $response, $arguments){
    $plot = $arguments['uid'] != 'new' ? 
      Plot::where('id', $arguments['uid'])->first() : 
      Plot::create(['name' => '']);
    $currentCharacter = self::getCurrentUser()["character"];
    
    $inParty = self::partOfPlot($plot, $currentCharacter["data"]);
    $isPublic = !!$plot->attr->where('name','public')->whereIn('value',['true','1'])->all();    
    $isRequest = $isPublic && $plot->characters()->count() == 1;
    $inParty = $inParty ? $inParty : $isRequest;
    $isLocked = !!$plot->attr->where('name','reviewed')->whereIn('value',['true','1'])->all();

    if($isLocked || (!$inParty && count($plot->characters) > 0)) {
      $this->flash->addMessage('error', "You can't edit this plot. ($plot->id)");
      return $response->withRedirect($this->router->pathFor('participant.home'));
    }
    
    $plot_attributes = [
      'public' =>                 !!$request->getParam('public'),
      'synopsis' =>                 $request->getParam('synopsis'),
      'submitted_for_review' =>     $request->getParam('submitted_for_review'),
    ];
    
    foreach($plot_attributes as $key => $value) {
      $value = is_null($value) ? false : $value;
      $debug[] = self::setAttribute($plot, $key, $value);
    }
        
    $plot->name = $request->getParam('name');
    $plot->description = $request->getParam('description');
    
    $characters = $request->getParam('character_ids');
    $characters = is_array($characters) ? $characters : [$characters];

    $groups = $request->getParam('group_ids');
    $groups = is_array($groups) ? $groups : [$groups];

    $parents = $request->getParam('parent_ids');
    $parents = is_array($parents) ? $parents : [$parents];
    
    if(count($parents)) {
      self::setAttribute($plot, array_fill(0, count($parents), 'parent_plot_id'), $parents);
    }

    foreach($characters as $character_id) {
      if( $currentCharacter['data']->id != $character_id) {
        $char = Character::where('id', $character_id)->first();
        if(!$char->user) continue;
        
        $notification_present = false;
        $char_user_notifications = $char->user->notifications()->where('seen_at', null)->get();
        foreach($char_user_notifications as $existing_notification) {
          if($existing_notification->plots()->where('id', $plot->id)->get()) {
            $notification_present = true;
            break;          
          }
        }
        if($notification_present) continue;
        
        $notification_data = [];
        if (
            $plot->characters()->where('character_id', $character_id)->get() 
//            || self::partOfRelationship($relationship, $char) //Only check other character containers if not directly attached
          ) {
          $notification_data = [
            'title' => $plot->name . ' updated!',
            'description' => $currentCharacter['data']->name . ' updated plot.',
            'icon' => 'eye',
          ];
        } else {
          $notification_data = [
            'title' => 'New plot!',
            'description' => "You have been added to \"${$plot->name}\".", 
            'icon' => 'exclamation-circle',
            'target' => $this->router->pathFor('participant.plot.pending') . '#' . $plot->id,
          ];
          
          $plot->attr()->attach(Attribute::firstOrCreate([
            'name' => 'pending',
            'value' => $char->id,
          ])->id);
        }
        
        self::notify($char->user, $plot, $notification_data);
      }
    }
    
    $plot->characters()->sync($characters);    
    $plot->groups()->sync($groups);
    $plot->plots()->sync($parents);
    
    if($plot->save()) {
      $this->flash->addMessage('success', "Plot updated."); 
    } else {
      $this->flash->addMessage('error', "Something went wrong saving the plot \"${$plot->name}\", sorry."); 
    }
    
    return $response->withRedirect(
      $this->router->pathFor('participant.plot', ['uid' => $plot->id])
    );
  }
  
  private function handlePlot($uid, $acceptRejct) {
    $currentCharacter = self::getCurrentUser()["character"];
    $plot = $currentCharacter["data"]->plot()->where('plot_id', $uid)->first();
    
    if(!$plot) {
      $this->flash->addMessage('error', "You don't seem to be part of this plot any more.");
      return $this->router->pathFor('participant.plot.pending');
    }
        
    $attr_pending = $plot->attr()->where([['name', 'pending'], ['value', $currentCharacter["data"]->id]])->first();
    if($attr_pending) {
      $plot->attr()->detach($attr_pending->id);
    } else {
      $this->flash->addMessage('error', "This plot is no longer pending.");
      return $this->router->pathFor('participant.plot.pending');      
    }
        
    if($acceptRejct == 'accept') {      
      $this->flash->addMessage('success', "Plot accepted.");

      foreach($plot->characters as $character) {
        if( $currentCharacter->id != $character->id) {
          $notification_data = [
            'title' => $character->name . ' accepted ' . $plot->attr->where('name', 'relationship_type')->get()->value . '!',
            'icon' => 'exclamation-circle',
          ];
          self::notify($character->user, $plot, $notification_data);
        }
      }
    }
    
    if($acceptRejct == 'reject') {
      $plot->characters()->detach($currentCharacter["data"]->id);      
      $this->flash->addMessage('error', "Plot rejected.");
      return $this->router->pathFor('participant.plot.pending');
    }
    
    return $this->router->pathFor('participant.plot.pending');
  }
  
  public function accept($request, $response, $arguments){
    return $response->withRedirect(
      self::handlePlot($arguments["uid"], 'accept')
    );
  }
  
  public function reject($request, $response, $arguments){
    return $response->withRedirect(
      self::handlePlot($arguments["uid"], 'reject')
    );
  }
  
  #=========================================================
  # Helpers
  #=========================================================
  
  private function getCharacers($model, $includeCurrent) {
    $characters = Character::where('name', '<>', '')->orderBy('name')->get();
    $character_list = [];
    foreach ($characters as $character) {
      if(!self::isNpc($character) && !$model->characters->where('id', $character->id)->first()) {
        $character_list[] = $character;
      }
    }
    return $character_list;
  }
  
  private function partOfPlot($model, $character = false) {    
    return (
      $this->container->auth->isWriter() ||
      $model->attr()->whereIn('name', ['source', 'target'])->where('value', $character->id)->get()->count() ||
      $model->characters()->where('character_id', $character->id)->get()->count() ||
      self::partOfGroups($model->groups(), $character->id)
    ) ? true : false;
  }
  
  private function partOfGroups($groups, $character_id) {
    foreach($groups as $group) {
      if($group->characters()->where('character.id', $character_id)->get()->count()) return true;
    }
    return false;
  }
  
  private function getPlotsInfo($character_id = 0) {
    if($character_id != 0) {      
      $plots = Character::where('id', $character_id)
        ->first()->plots;
    } else {
      $plots = Plot::whereHas(
          'attr', function ($query) {
            $query->where([['name', 'public'], ['value', '1']])
              ->orWhere([['name', 'public'], ['value', 'true']]);        
          }
        )->whereDoesntHave(
          'attr', function ($query) {
            $query->where('name', 'pending');
          }
        )->with('attr')
        ->get();
    }
    return $plots;
  }
  
  private function pendingPlots($character) {
    $pending_plots = [];
    foreach($character->plots as $plot) {
      if($plot->attr()->where([['name', 'pending'], ['value', $character->id]])->first()) {
        $pending_plots[] = $plot;
      }
    }
    return $pending_plots;
  }
}