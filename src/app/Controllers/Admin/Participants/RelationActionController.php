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
  private function handlePostData($request) {
    $credentials = [
      'name' => $request->getParam('name'),
      'description' => $request->getParam('description'),
    ];   
    
    $attributes = [ 'keys' => $request->getParam('attrKey'), 'values' => $request->getParam('attrVal')];
    $attribute_ids = [];
    
    foreach ($attributes['keys'] as $i => $attr_key) {
      if(strlen($attr_key)) {
        $attribute_ids[] = Attribute::firstOrCreate([
          'name' => $attr_key, 
          'value' => $attributes['values'][$i]
        ])->id;
      }
    }
    
    $groups = $request->getParam('group_ids');
    $groups = is_array($groups) ? $groups : [$groups];

    $characters = $request->getParam('character_ids');
    $characters = is_array($characters) ? $characters : [$characters];   
    
    return [ 
      'values' => $credentials, 
      'attributes' => $attribute_ids,
      'groups' => $groups,
      'characters' => $characters,
    ];    
    
    return [$credentials, $attribute_ids];
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
      'new' => true
    ]);
    
    return $this->view->render($response, 'admin/participants/relations/edit.html', [
      'characters' => Character::orderBy('name')->get(),
      'groups' => Group::orderBy('name')->get(),
    ]);
  }
  
  public function postAdd($request, $response, $arguments)
  {    
    // update data
    $requestData = self::handlePostData($request);
    $item = Relation::create($requestData['values']);
    
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
  
  public function edit($request, $response, $arguments)
  {
     $this->container->view->getEnvironment()->addGlobal('current', [
      'data' => Relation::where('id', $arguments['uid'])->first(),
    ]);
    
    return $this->view->render($response, 'admin/participants/relations/edit.html', [
      'characters' => Character::orderBy('name')->get(),
      'groups' => Group::orderBy('name')->get(),
    ]);
  }
  
  public function postEdit($request, $response, $arguments)
  {
    $item = Relation::where('id', $arguments['uid'])->first();
    $requestData = self::handlePostData($request);
    
    // update data
    if($item->update($requestData['values'])) {
      $item->attr()->sync($requestData['attributes']);
      $item->groups()->sync($requestData['groups']);
      $item->characters()->sync($requestData['characters']);
      
      $this->flash->addMessage('success', "Relation {$requestData[0]['name']} have been saved.");
    } else {
      $this->flash->addMessage('error', "The relation could not be saved.");
    }
    
    if( $request->getParam('selfsave') == 1 ) {
      return $response->withRedirect($this->router->pathFor('admin.relation.edit', ['uid' => $arguments['uid']]) . "#saved");
    } else {
      return $response->withRedirect($this->router->pathFor('admin.relation.list'));
    }
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