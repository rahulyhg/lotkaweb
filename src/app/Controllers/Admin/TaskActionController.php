<?php

namespace App\Controllers\Admin;

use App\Models\Task;
use App\Models\User;
use App\Controllers\Controller;
use Respect\Validation\Validator as v;
use Slim\Views\Twig as View;

class TaskActionController extends Controller
{
  public function index($request, $response, $arguments)
  {
    $tasks = Task::all();
    
    return $this->view->render($response, 'admin/task/list.html', [
      'tasks' => $tasks
    ]);
  }
  
  public function add($request, $response, $arguments)
  {
    $this->container->view->getEnvironment()->addGlobal('current', [
      'data' => [],
      'new' => true
    ]);
    
    return $this->view->render($response, 'admin/task/edit.html', [
      'users' => User::query()->orderBy('displayname')->get()
    ]);
  }
  
  public function postAdd($request, $response, $arguments)
  {
    $credentials = [
      'title' => $request->getParam('title'),
      'description' => $request->getParam('description'),
      'priority' => $request->getParam('priority'),
      'due_date' => $request->getParam('due_date'),
      'user_id' => $request->getParam('user_id'),
      'status' => $request->getParam('status'),
    ];
    
    // update data
    if(Task::create($credentials)) {
      $this->flash->addMessage('success', "Task details have been saved.");
    } else {
      $this->flash->addMessage('error', "The task could not be saved.");
    }
    return $response->withRedirect($this->router->pathFor('admin.tasks.all'));
  }  
  
  public function edit($request, $response, $arguments)
  {
    $taskData = Task::where('id', $arguments['uid'])->first();
    
    if(!$taskData) {
      $this->flash->addMessage('error', "No Task with ID '{$arguments['uid']}' found.");
      return $response->withRedirect($this->router->pathFor('admin.tasks.all'));     
    }

    $this->container->view->getEnvironment()->addGlobal('current', [
      'data' => $ticketData,
    ]);
    
    return $this->view->render($response, 'admin/task/edit.html', [
      'users' => User::query()->orderBy('displayname')->get()
    ]);
  }
  
  public function postEdit($request, $response, $arguments)
  {
    $task = Ticket::where('id', $arguments['uid'])->first();
    
    if(!$task) {
      $this->flash->addMessage('error', "No task with ID '{$arguments['uid']}' found.");
      return $response->withRedirect($this->router->pathFor('admin.tasks.all'));     
    }
    
    $credentials = [
      'title' => $request->getParam('title'),
      'description' => $request->getParam('description'),
      'priority' => $request->getParam('priority'),
      'due_date' => $request->getParam('due_date'),
      'user_id' => $request->getParam('user_id'),
      'status' => $request->getParam('status'),
    ];
    
    // update data
    if($task->update($credentials)) {
      $this->flash->addMessage('success', "Task details for '{$credentials['sku']}' have been changed.");
    } else {
      $this->flash->addMessage('error', "The task could not be updated.");
    }
    return $response->withRedirect($this->router->pathFor('admin.tasks.all'));
  }  
  
  public function delete($request, $response, $arguments)
  {
    $task = Task::where('id', $arguments['uid']);
    $task->delete();

    $this->flash->addMessage('success', "Task has been deleted.");
    return $response->withRedirect($this->router->pathFor('admin.tasks.all'));
  }  
  
  public function todo($request, $response, $arguments)
  {
    
  }
}