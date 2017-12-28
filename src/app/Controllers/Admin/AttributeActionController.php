<?php

namespace App\Controllers\Admin;

use App\Models\Attribute;
use App\Models\Character;
use App\Models\Group;
use App\Models\Plot;
use App\Models\Post;
use App\Models\Relation;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Media;
use App\Controllers\Controller;
use Respect\Validation\Validator as v;
use Slim\Views\Twig as View;

class AttributeActionController extends Controller
{
  private function options($attr = false) {
    return [
      'characters'  => Character::where('name', '<>', '')->orderBy('name')->get(),
      'groups'      => Group::where('name', '<>', '')->orderBy('name')->get(),
      'plots'       => Plot::where('name', '<>', '')->orderBy('name')->get(),
      'posts'       => Post::where('title', '<>', '')->orderBy('title')->get(),
      'rel'         => Relation::where('name', '<>', '')->orderBy('name')->get(),
      'tickets'     => Ticket::where('sku', '<>', '')->orderBy('sku')->get(),
      'users'       => User::where('displayname', '<>', '')->orderBy('displayname')->get(),
      'media'       => Media::where('name', '<>', '')->orderBy('name')->get(), 
    ];
  }
  
  private function handlePostData($request) {
    return [ 
      'values' => [ 
        'keys' => $request->getParam('name'), 
        'values' => $request->getParam('value')
      ],
    ];    
  }
  
  private function save($requestData, $request, $response, $arguments) {
    // update data
    $item = Attribute::firstOrCreate(['id' => $arguments['uid']]);
    $item->update($requestData['values']);
    
    if($item->id) {  
      $this->flash->addMessage('success', "Details have been saved.");
    } else {
      $this->flash->addMessage('error', "The details could not be saved.");
    }

    return $response->withRedirect($this->router->pathFor('admin.attributes.list'));
  }
  
  public function index($request, $response, $arguments)
  {
    $item = Attribute::orderBy('name');
    
    #die(var_dump($item->toSql()));    
    
    return $this->view->render($response, "admin/attributes/list.html", [
      'list' => $item->get(),
    ]);
  }
  
  public function add($request, $response, $arguments)
  {
    $this->container->view->getEnvironment()->addGlobal('current', [
      'data' => [],
      'new' => true
    ]);
    
    return $this->view->render($response, 'admin/attributes/edit.html', self::options());
  }
  
  public function post($request, $response, $arguments)
  {    
    return self::save(self::handlePostData($request), $request, $response, $arguments);
  }
  
  public function edit($request, $response, $arguments)
  {
    $item = Attribute::where('id', $arguments['uid'])->first();
    $this->container->view->getEnvironment()->addGlobal('current', [
      'data' => $item,
      'characters' => $item->characters()->get(),
      'groups' => $item->groups()->get(),
      'plots' => $item->plots()->get(),
      'posts' => $item->posts()->get(),
      'rel' => $item->rel()->get(),
      'tickets' => $item->tickets()->get(),
      'users' => $item->users()->get(),
      'media' => $item->media()->get(),
    ]);
    
    return $this->view->render($response, 'admin/attributes/edit.html', self::options($item));
  }
  
  public function delete($request, $response, $arguments)
  {
    return "TODO";
  }
}