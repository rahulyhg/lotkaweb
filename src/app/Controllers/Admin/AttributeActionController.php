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
use App\Models\Notification;
//use App\Models\ItemList;
//use App\Models\ListItem;

use App\Controllers\Controller;
use Respect\Validation\Validator as v;
use Slim\Views\Twig as View;

class AttributeActionController extends Controller
{
  private function options($attr = false) {
    return [
      'characters'    => Character::orderBy('name')->get(),
      'groups'        => Group::orderBy('name')->get(),
      'plots'         => Plot::orderBy('name')->get(),
      'posts'         => Post::orderBy('title')->get(),
      'rel'           => Relation::orderBy('name')->get(),
      'tickets'       => Ticket::orderBy('sku')->get(),
      'users'         => User::orderBy('displayname')->get(),
      'media'         => Media::orderBy('name')->get(), 
      'notifications' => Notification::orderBy('title')->get(),
//      'lists'         => ItemList::orderBy('name')->get(),
//      'listItems'     => ListItem::orderBy('name')->get(),
    ];
  }
  
  private function handlePostData($request) {    
    return [ 
      'values' => [ 
        'name'      => $request->getParam('name'), 
        'value'     => $request->getParam('value')
      ],
      'characters'    => is_null($request->getParam('character_ids')) ? [] : $request->getParam('character_ids'),
      'groups'        => is_null($request->getParam('group_ids')) ? [] : $request->getParam('group_ids'),
      'plots'         => is_null($request->getParam('plot_ids')) ? [] : $request->getParam('plot_ids'),
      'posts'         => is_null($request->getParam('post_ids')) ? [] : $request->getParam('post_ids'),
      'rel'           => is_null($request->getParam('rel_ids')) ? [] : $request->getParam('rel_ids'),
      'tickets'       => is_null($request->getParam('ticket_ids')) ? [] : $request->getParam('ticket_ids'),
      'users'         => is_null($request->getParam('user_ids')) ? [] : $request->getParam('user_ids'),
      'media'         => is_null($request->getParam('media_ids')) ? [] : $request->getParam('media_ids'), 
      'notifications' => is_null($request->getParam('notification_ids')) ? [] : $request->getParam('notification_ids'), 
//      'lists'         => is_null($request->getParam('list_ids')) ? [] : $request->getParam('list_ids'),
//      'listItems'     => is_null($request->getParam('listItem_ids')) ? [] : $request->getParam('listItem_ids'),
    ];
  }
  
  private function save($requestData, $request, $response, $arguments) {
    // update data
    $item = Attribute::firstOrCreate(['id' => $arguments['uid']]);
    $item->update($requestData['values']);

    $item->characters()->sync($requestData['characters']);
    $item->groups()->sync($requestData['groups']);
    $item->plots()->sync($requestData['plots']);
    $item->posts()->sync($requestData['posts']);
    $item->rel()->sync($requestData['rel']);
    $item->tickets()->sync($requestData['tickets']);
    $item->users()->sync($requestData['users']);
    $item->media()->sync($requestData['media']);
    $item->notifications()->sync($requestData['notifications']);
//    $item->lists()->sync($requestData['notifications']);
//    $item->listItems()->sync($requestData['notifications']);

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
      'data'          => $item,
      'characters'    => $item->characters()->get(),
      'groups'        => $item->groups()->get(),
      'plots'         => $item->plots()->get(),
      'posts'         => $item->posts()->get(),
      'rel'           => $item->rel()->get(),
      'tickets'       => $item->tickets()->get(),
      'users'         => $item->users()->get(),
      'media'         => $item->media()->get(),
      'notifications' => $item->notifications()->get(),
//      'list'          => $item->lists()->get(),
//      'listItem'      => $item->listItems()->get(),
    ]);
    
    if(is_null($item->value)) $item->value = '';
    return $this->view->render($response, 'admin/attributes/edit.html', self::options($item));
  }
  
  public function delete($request, $response, $arguments)
  {
    $item = Attribute::where('id', $arguments['uid'])->first();

    $item->characters()->sync([]);
    $item->groups()->sync([]);
    $item->plots()->sync([]);
    $item->posts()->sync([]);
    $item->rel()->sync([]);
    $item->tickets()->sync([]);
    $item->users()->sync([]);
    $item->media()->sync([]);
    $item->notifications()->sync([]);
//    $item->lists()->sync([]);
//    $item->listItems()->sync([]);

    if($item->delete()) {
      $this->flash->addMessage('success', "Attribute has been removed from all entities and deleted.");
    } else {
      $this->flash->addMessage('error', "The attribute could not be deleted.");
    }

    return $response->withRedirect($this->router->pathFor('admin.attributes.list'));    
  }
}