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

class GroupActionController extends Controller
{
  private function handlePostData($request) {
    $credentials = [
      'name' => $request->getParam('name'),
      'description' => $request->getParam('description'),
    ];   
    
    $attributes = [ 'keys' => $request->getParam('attrKey'), 'values' => $request->getParam('attrVal')];
    $attribute_ids = [];
    
    foreach ($attributes['keys'] as $i => $attr_key) {
      $attribute_ids[] = Attribute::firstOrCreate([
        'name' => $attr_key, 
        'value' => $attributes['values'][$i]
      ])->id;
    }
    
    return [$credentials, $attribute_ids];
  }
  
  public function index($request, $response, $arguments)
  {
    $item = Group::orderBy('name');
    /*
    //Filter by attribute
    $item = Group::whereHas(
        'attr', function ($query) {
            $query->where([
              ['name','NPC'], ['value','yes']
            ]);
        }
    )
    ->with('attr');
    */
    
    return $this->view->render($response, "admin/participants/groups/list.html", [
      'groups' => $item->get(),
    ]);
  }
  
  public function add($request, $response, $arguments)
  {
    $this->container->view->getEnvironment()->addGlobal('current', [
      'data' => [],
      'new' => true
    ]);
    
    return $this->view->render($response, 'admin/participants/groups/edit.html', [
      'users' => User::orderBy('displayname')->get(),
    ]);
  }
  
  public function postAdd($request, $response, $arguments)
  {    
    // update data
    $requestData = self::handlePostData($request);
    $item = Group::create($requestData[0]);
    
    // update data
    if($item->id) {
      $item->attr()->sync($requestData[1]);
      $this->flash->addMessage('success', "Group details have been saved.");
    } else {
      $this->flash->addMessage('error', "The group could not be saved.");
    }
    
    if( $request->getParam('selfsave') == 1 ) {
      return $response->withRedirect($this->router->pathFor('admin.group.edit', ['uid' => $arguments['uid']]) . "#saved");
    } else {
      return $response->withRedirect($this->router->pathFor('admin.group.list'));
    }
  }
  
  public function edit($request, $response, $arguments)
  {
     $this->container->view->getEnvironment()->addGlobal('current', [
      'data' => Group::where('id', $arguments['uid'])->first(),
    ]);
    
    return $this->view->render($response, 'admin/participants/groups/edit.html', [
      'users' => User::orderBy('displayname')->get(),
    ]);
  }
  
  public function postEdit($request, $response, $arguments)
  {
    $item = Group::where('id', $arguments['uid'])->first();
    $requestData = self::handlePostData($request);
    
    // update data
    if($item->update($requestData[0])) {
      $item->attr()->sync($requestData[1]);
      $this->flash->addMessage('success', "Group {$requestData[0]['name']} have been saved.");
    } else {
      $this->flash->addMessage('error', "The group could not be saved.");
    }
    
    if( $request->getParam('selfsave') == 1 ) {
      return $response->withRedirect($this->router->pathFor('admin.group.edit', ['uid' => $arguments['uid']]) . "#saved");
    } else {
      return $response->withRedirect($this->router->pathFor('admin.group.list'));
    }
  }

  public function delete($request, $response, $arguments)
  {
    $group = Group::where('id', $arguments['uid'])->first();
    
    $group->characters()->sync([]);
    $group->attr()->sync([]);
    $group->users()->sync([]);
    $group->groups()->sync([]);
    $group->plots()->sync([]);
    $group->rel()->sync([]);

    $group->delete();
    $this->flash->addMessage('warning', "Group {$group->name} was deleted.");
    return $response->withRedirect($this->router->pathFor('admin.group.list'));
  }
}