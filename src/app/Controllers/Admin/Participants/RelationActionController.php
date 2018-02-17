<?php

namespace App\Controllers\Admin\Participants;

use App\Models\Character;
use App\Models\Attribute;
use App\Models\Group;
use App\Models\User;
use App\Models\Relation;
use App\Models\Plot;
use App\Controllers\Controller;
use Respect\Validation\Validator as v;
use Slim\Views\Twig as View;

class RelationActionController extends Controller
{
  
  private function relationship_attributes() {
    return [
      'relationship_icon', 'relationship_type', 'source', 'target', 'public',
    ];
  }

  private function relationOptions() {
    return [
      'users' => User::orderBy('displayname')->get(),
      'characters' => Character::orderBy('name')->get(),
      'groups' => Group::orderBy('name')->get(),
      'set_attr' => self::relationship_attributes(),
      'types' => self::relationTypes(),
      'icons' => self::relationIcons(),
    ];
  }
  
  private function relationTypes() {
    return Attribute::where('name', 'relationship_type')->get();
  }
  
  private function relationIcons() {
    return ["icon-adjust", "icon-asterisk", "icon-ban-circle", "icon-bar-chart", 
            "icon-barcode", "icon-beaker", "icon-bell", "icon-bolt", "icon-book", 
            "icon-bookmark", "icon-bookmark-empty", "icon-briefcase", "icon-bullhorn", 
            "icon-calendar", "icon-camera", "icon-camera-retro", "icon-certificate", 
            "icon-check", "icon-check-empty", "icon-cloud", "icon-cog", "icon-cogs", 
            "icon-comment", "icon-comment-alt", "icon-comments", "icon-comments-alt", 
            "icon-credit-card", "icon-dashboard", "icon-download", "icon-download-alt", 
            "icon-edit", "icon-envelope", "icon-envelope-alt", "icon-exclamation-sign", 
            "icon-external-link", "icon-eye-close", "icon-eye-open", "icon-facetime-video", 
            "icon-film", "icon-filter", "icon-fire", "icon-flag", "icon-folder-close", 
            "icon-folder-open", "icon-gift", "icon-glass", "icon-globe", "icon-group", 
            "icon-hdd", "icon-headphones", "icon-heart", "icon-heart-empty", "icon-home", 
            "icon-inbox", "icon-info-sign", "icon-key", "icon-leaf", "icon-legal", 
            "icon-lemon", "icon-lock", "icon-unlock", "icon-magic", "icon-magnet", 
            "icon-map-marker", "icon-minus", "icon-minus-sign", "icon-money", "icon-move", 
            "icon-music", "icon-off", "icon-ok", "icon-ok-circle", "icon-ok-sign", 
            "icon-pencil", "icon-picture", "icon-plane", "icon-plus", "icon-plus-sign", 
            "icon-print", "icon-pushpin", "icon-qrcode", "icon-question-sign", 
            "icon-random", "icon-refresh", "icon-remove", "icon-remove-circle", 
            "icon-remove-sign", "icon-reorder", "icon-resize-horizontal", 
            "icon-resize-vertical", "icon-retweet", "icon-road", "icon-rss", 
            "icon-screenshot", "icon-search", "icon-share", "icon-share-alt", 
            "icon-shopping-cart", "icon-signal", "icon-signin", "icon-signout", 
            "icon-sitemap", "icon-sort", "icon-sort-down", "icon-sort-up", "icon-star", 
            "icon-star-empty", "icon-star-half", "icon-tag", "icon-tags", "icon-tasks", 
            "icon-thumbs-down", "icon-thumbs-up", "icon-time", "icon-tint", "icon-trash", 
            "icon-trophy", "icon-truck", "icon-umbrella", "icon-upload", "icon-upload-alt", 
            "icon-user", "icon-user-md", "icon-volume-off", "icon-volume-down", 
            "icon-volume-up", "icon-warning-sign", "icon-wrench", "icon-zoom-in", 
            "icon-zoom-out", "icon-file", "icon-cut", "icon-copy", "icon-paste", 
            "icon-save", "icon-undo", "icon-repeat", "icon-paper-clip", "icon-text-height", 
            "icon-text-width", "icon-align-left", "icon-align-center", "icon-align-right", 
            "icon-align-justify", "icon-indent-left", "icon-indent-right", "icon-font", 
            "icon-bold", "icon-italic", "icon-strikethrough", "icon-underline", "icon-link", 
            "icon-columns", "icon-table", "icon-th-large", "icon-th", "icon-th-list", 
            "icon-list", "icon-list-ol", "icon-list-ul", "icon-list-alt", "icon-arrow-down", 
            "icon-arrow-left", "icon-arrow-right", "icon-arrow-up", "icon-chevron-down", 
            "icon-circle-arrow-down", "icon-circle-arrow-left", "icon-circle-arrow-right", 
            "icon-circle-arrow-up", "icon-chevron-left", "icon-caret-down", "icon-caret-left", 
            "icon-caret-right", "icon-caret-up", "icon-chevron-right", "icon-hand-down", 
            "icon-hand-left", "icon-hand-right", "icon-hand-up", "icon-chevron-up", 
            "icon-play-circle", "icon-play", "icon-pause", "icon-stop", "icon-step-backward", 
            "icon-fast-backward", "icon-backward", "icon-forward", "icon-fast-forward", 
            "icon-step-forward", "icon-eject", "icon-fullscreen", "icon-resize-full", 
            "icon-resize-small", "icon-phone", "icon-phone-sign", "icon-facebook", 
            "icon-facebook-sign", "icon-twitter", "icon-twitter-sign", "icon-github", 
            "icon-github-sign", "icon-linkedin", "icon-linkedin-sign", "icon-pinterest", 
            "icon-pinterest-sign"];
  }
  
  private function handlePostData($request) {
    $credentials = [
      'name' => $request->getParam('name'),
      'description' => $request->getParam('description'),
    ];   
    
    $attributes = [ 
      'keys' => $request->getParam('attrKey'), 
      'values' => $request->getParam('attrVal')
    ];
    
    foreach (self::relationship_attributes() as $attr) {
      if ( strlen($request->getParam($attr)) ) {
        $attributes['keys'][] = $attr;
        $attributes['values'][] = $request->getParam($attr);
      }
    }
    
    $attribute_ids = [];
    
    foreach ($attributes['keys'] as $i => $attr_key) {
      if(strlen($attr_key)) {
        $attribute_ids[] = Attribute::firstOrCreate([
          'name' => $attr_key, 
          'value' => $attributes['values'][$i]
        ])->id;
      }
    }
    
    if($request->getParam('new_type')) {
      $attribute_ids[] = Attribute::create([
        'name' => 'relationship_type', 
        'value' => $request->getParam('new_type')
      ])->id;
    }       
    
    $groups = $request->getParam('group_ids');
    $groups = is_array($groups) ? $groups : [$groups];

    $characters = $request->getParam('character_ids');
    $characters = is_array($characters) ? $characters : [$characters];   
    if($request->getParam('source')) $characters = array_merge($characters, [$request->getParam('source')]);
    if($request->getParam('target')) $characters = array_merge($characters, [$request->getParam('target')]);
    
    return [ 
      'values' => $credentials, 
      'attributes' => $attribute_ids,
      'groups' => $groups,
      'characters' => $characters,
    ];
  }
  
  private function saveRelation($id, $data, $credentials) {
    $item = Relation::create(['name' => $credentials['name']]);
    if($item->id) {
      $attribute_ids = [];
      foreach ($data as $name => $value) {
        $attribute_ids[] = Attribute::firstOrCreate([
          'name' => $name, 
          'value' => $value
        ])->id;
      }

      $item->attr()->sync($attribute_ids);
      $item->characters()->sync($credentials["characters"]);
      return true;
    } else {
      $this->flash->addMessage('error', "The relationship ID:" . $id . " could not be saved.");
      return false;
    }
  }
  
  private function save($requestData, $request, $response, $arguments) {
    // update data
    $item = Relation::firstOrCreate(['id' => $arguments['uid']]);
    $item->update($requestData['values']);
    
    // update data
    if($item->id) {
      $item->attr()->sync($requestData['attributes']);
      $item->groups()->sync($requestData['groups']);
      $item->characters()->sync($requestData['characters']);
      
      $this->flash->addMessage('success', "Relation details have been saved.");
    } else {
      $this->flash->addMessage('error', "The relation could not be saved.");
    }
    
    if( $request->getParam('selfsave') == 1 ) {
      return $response->withRedirect($this->router->pathFor('admin.relation.edit', ['uid' => $arguments['uid']]) . "#saved");
    } else {
      return $response->withRedirect($this->router->pathFor('admin.relation.list'));
    }    
  }
  public function index($request, $response, $arguments)
  {
    $item = Relation::orderBy('name');
    /*
    //Filter by attribute
    $item = Plot::whereHas(
        'attr', function ($query) {
            $query->where([
              ['name','NPC'], ['value','yes']
            ]);
        }
    )
    ->with('attr');
    */
    
    return $this->view->render($response, "admin/participants/relations/list.html", [
      'relations' => $item->get(),
    ]);
  }
  
  public function add($request, $response, $arguments)
  {
    $this->container->view->getEnvironment()->addGlobal('current', [
      'data' => [],
      'attr' => [],
      'new' => true
    ]);
    
    return $this->view->render($response, 'admin/participants/relations/edit.html', self::relationOptions());
  }
  
  public function generate($request, $response, $arguments)
  {
    return $this->view->render($response, 'admin/participants/relations/generate.html', [
    ]);
  }

  public function postGenerate($request, $response, $arguments)
  {    
    $relation_data = [
      "source"            => $request->getParam("id"),
      "relationship_type" => $request->getParam("nature"),
      "target"            => $request->getParam("char_id"),     
    ];
    
    $relation_generations = [];
    foreach ( $relation_data["source"] as $i => $id ) {
      $relation_generations[$id] = self::saveRelation($id, [
        "relationship_type" => $relation_data["relationship_type"][$i],
      ], [
        "name"              => $relation_data["relationship_type"][$i],
        "characters"        => [$relation_data["source"][$i], $relation_data["target"][$i]]
      ]);
    }
    
    //die(var_dump($character_generations));
    
    if( !in_array(false, $relation_generations, true) ) {
      $this->flash->addMessage('success', count($relation_data["source"]) . " Relations created.");      
    }
    
    return $response->withRedirect($this->router->pathFor('admin.relation.list'));
  }
  
  public function post($request, $response, $arguments)
  {    
    return self::save(self::handlePostData($request), $request, $response, $arguments);
  }
  
  public function edit($request, $response, $arguments)
  {
    $relation = Relation::where('id', $arguments['uid'])->first();
     $this->container->view->getEnvironment()->addGlobal('current', [
      'data' => $relation,
      'attr' => self::mapAttributes( $relation->attr ),
    ]);
    
    return $this->view->render($response, 'admin/participants/relations/edit.html', self::relationOptions());
  }

  public function delete($request, $response, $arguments)
  {
    $item = Relation::where('id', $arguments['uid'])->first();
    
    $item->attr()->sync([]);
    $item->groups()->sync([]);
    $item->characters()->sync([]);

    $item->delete();
    $this->flash->addMessage('warning', "Relation {$item->name} was deleted.");
    return $response->withRedirect($this->router->pathFor('admin.relation.list'));
  }
}