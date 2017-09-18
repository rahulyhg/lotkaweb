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

class CharacterActionController extends Controller
{

  private function handlePostData($request) {
    $credentials = [
      'name' => $request->getParam('name'),
      'description' => $request->getParam('description'),
      'user_id' => $request->getParam('user_id'),
    ];   
    
    $attributes = [ 'keys' => $request->getParam('attrKey'), 'values' => $request->getParam('attrVal')];
    $attribute_ids = [];
    
    foreach ($attributes['keys'] as $i => $attr_key) {
      $attribute_ids[] = Attribute::firstOrCreate([
        'name' => $attr_key, 
        'value' => $attributes['values'][$i]
      ])->id;
    }
    
    $groups = $request->getParam('group_ids');
    $groups = is_array($groups) ? $groups : [$groups];
    
    $relations = $request->getParam('relations_ids');
    $relations = is_array($relations) ? $relations : [$relations];
    
      
    return [ 
      'values' => $credentials, 
      'attributes' => $attribute_ids,
      'groups' => $groups,
      'relations' => $relations,
    ];
  }
  
  public function index($request, $response, $arguments)
  {
    $characters = Character::orderBy('name');
    /*
    //Filter by attribute
    $characters = Character::whereHas(
        'attr', function ($query) {
            $query->where([
              ['name','NPC'], ['value','yes']
            ]);
        }
    )
    ->with('attr');
    */
    
    return $this->view->render($response, 'admin/participants/characters/list.html', [
      'characters' => $characters->get(),
    ]);
  }
  
  public function add($request, $response, $arguments)
  {
    $this->container->view->getEnvironment()->addGlobal('current', [
      'data' => [],
      'new' => true
    ]);
    
    return $this->view->render($response, 'admin/participants/characters/edit.html', [
      'users' => User::orderBy('displayname')->get(),
      'groups' => Group::orderBy('name')->get(),
      'plots' => Plot::orderBy('name')->get(),
      'relations' => Relation::orderBy('name')->get(),
    ]);
  }
  
  public function postAdd($request, $response, $arguments)
  {    
    // update data
    $requestData = self::handlePostData($request);
    $post = Character::create($requestData['values']);
    
    // update data
    if($post->id) {
      $post->attr()->sync($requestData['attributes']);
      $post->groups()->sync($requestData['groups']);
      $post->rel()->sync($requestData['relations']);
      
      $this->flash->addMessage('success', "Post details have been saved.");
    } else {
      $this->flash->addMessage('error', "The post could not be saved.");
    }
    
    if( $request->getParam('selfsave') == 1 ) {
      return $response->withRedirect($this->router->pathFor('admin.character.edit', ['uid' => $arguments['uid']]) . "#saved");
    } else {
      return $response->withRedirect($this->router->pathFor('admin.character.list'));
    }
  }
  
  public function edit($request, $response, $arguments)
  {
     $this->container->view->getEnvironment()->addGlobal('current', [
      'data' => Character::where('id', $arguments['uid'])->first(),
    ]);
    
    return $this->view->render($response, 'admin/participants/characters/edit.html', [
      'users' => User::orderBy('displayname')->get(),
      'groups' => Group::orderBy('name')->get(),
      'plots' => Plot::orderBy('name')->get(),
      'relations' => Relation::orderBy('name')->get(),
      'debug' => Character::where('id', $arguments['uid'])->first()->rel()->get()
    ]);
  }
  
  public function postEdit($request, $response, $arguments)
  {
    $character = Character::where('id', $arguments['uid'])->first();
    $requestData = self::handlePostData($request);
    
    // update data
    if($character->update($requestData['values'])) {
      $character->attr()->sync($requestData['attributes']);
      $character->groups()->sync($requestData['groups']);
      $character->rel()->sync($requestData['relations']);
      
      $this->flash->addMessage('success', "Character {$requestData['values']['name']} have been saved.");
    } else {
      $this->flash->addMessage('error', "The character could not be saved.");
    }
    
    if( $request->getParam('selfsave') == 1 ) {
      return $response->withRedirect($this->router->pathFor('admin.character.edit', ['uid' => $arguments['uid']]) . "#saved");
    } else {
      return $response->withRedirect($this->router->pathFor('admin.character.list'));
    }
  }


  public function delete($request, $response, $arguments)
  {
    $item = Character::where('id', $arguments['uid'])->first();
    
    $item->attr()->sync([]);
    $item->groups()->sync([]);
    $item->plots()->sync([]);
    $item->rel()->sync([]);

    $item->delete();
    $this->flash->addMessage('warning', "Character {$item->name} was deleted.");
    return $response->withRedirect($this->router->pathFor('admin.character.list'));
  }
}