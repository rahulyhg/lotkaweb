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
  
  private function attributes() {
    return [
      'visible', 'synopsis',
    ];
  }
  
  private function options() {
    return [
      'users' => User::orderBy('displayname')->get(),
      'characters' => Character::orderBy('name')->get(),
      'groups' => Group::orderBy('name')->get(),
      'plots' => Plot::orderBy('name')->get(),
      'relations' => Relation::orderBy('name')->get(),
      'set_attr' => self::attributes(),
    ];
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
    
    foreach (self::attributes() as $attr) {
      if ( strlen($request->getParam($attr)) ) {
        $attributes['keys'][] = $attr;
        $attributes['values'][] = $request->getParam($attr);
      }
    }
    
    $attribute_ids = [];
    
    foreach ($attributes['keys'] as $i => $attr_key) {
      $attribute_ids[] = Attribute::firstOrCreate([
        'name' => $attr_key, 
        'value' => $attributes['values'][$i]
      ])->id;
    }
    
    return [ 
      'values' => $credentials, 
      'attributes' => $attribute_ids,
      'characters' => self::paramToArray($request, 'character_ids'),
      'groups' => self::paramToArray($request, 'group_ids'),
      'relations' => self::paramToArray($request, 'relation_ids'),
      'users' => self::paramToArray($request, 'user_ids'),
      'plots' => self::paramToArray($request, 'plot_ids'),
    ];    
  }
  
  private function save($requestData, $request, $response, $arguments) {
    // update data
    $item = Group::firstOrCreate(['id' => $arguments['uid']]);
    $item->update($requestData['values']);
    
    if($item->id) {
      $item->attr()->sync($requestData['attributes']);
      $item->groups()->sync($requestData['groups']);
      $item->rel()->sync($requestData['relations']);
      $item->users()->sync($requestData['users']);
      $item->characters()->sync($requestData['characters']);
      $item->plots()->sync($requestData['plots']);
      
      $this->flash->addMessage('success', "Details have been saved.");
    } else {
      $this->flash->addMessage('error', "The details could not be saved.");
    }
    
    if( $request->getParam('selfsave') == 1 ) {
      return $response->withRedirect($this->router->pathFor('admin.group.edit', ['uid' => $arguments['uid']]) . "#saved");
    } else {
      return $response->withRedirect($this->router->pathFor('admin.group.list'));
    }    
  }
  
  public function index($request, $response, $arguments)
  {
    $item = Group::orderBy('name');
    
    return $this->view->render($response, "admin/participants/groups/list.html", [
      'groups' => $item->get(),
    ]);
  }
  
  public function add($request, $response, $arguments)
  {
    $this->container->view->getEnvironment()->addGlobal('current', [
      'data' => [],
      'attr' => [],
      'new' => true
    ]);
    
    return $this->view->render($response, 'admin/participants/groups/edit.html', self::options());
  }
  
  public function post($request, $response, $arguments)
  {    
    return self::save(self::handlePostData($request), $request, $response, $arguments);
  }
  
  public function edit($request, $response, $arguments)
  {
    $item = Group::where('id', $arguments['uid'])->first();
    $this->container->view->getEnvironment()->addGlobal('current', [
      'data' => $item,
      'attr' => self::mapAttributes( $item->attr ),
    ]);
    
    return $this->view->render($response, 'admin/participants/groups/edit.html', self::options());
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