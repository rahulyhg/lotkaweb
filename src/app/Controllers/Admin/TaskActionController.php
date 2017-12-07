<?php

namespace App\Controllers\Admin;

use App\Models\Task;
use App\Models\User;

use App\Controllers\Controller;
use Respect\Validation\Validator as v;
use Slim\Views\Twig as View;

class TaskActionController extends Controller
{
  private function listTasksQuery($status) {
    $DB = $this->db->getDatabaseManager();
    
    return Task::query()
      ->leftJoin('users', 'tasks.user_id', '=', 'users.id')
      ->select(
        'tasks.*',
        $DB->raw("IFNULL(NULLIF(users.displayname,users.email), users.username) as user_name, users.username as user_link")
      )->whereIn('status', $status)->orderBy('priority', 'desc')->orderBy('due_date');
  }
    
  
  public function index($request, $response, $arguments)
  {
    $tasks = self::listTasksQuery([0,2,3]);
    
    //die(var_dump($tasks->toSql()));
    
    return $this->view->render($response, 'admin/task/list.html', [
      'tasks' => $tasks->get(),
    ]);
  }
  
  public function allTasks($request, $response, $arguments)
  {
    $tasks = self::listTasksQuery([0,1,2,3]);
    
    //die(var_dump($tasks->toSql()));
    
    return $this->view->render($response, 'admin/task/list.html', [
      'tasks' => $tasks->get(),
    ]);
  }  
  
  public function blockedTasks($request, $response, $arguments)
  {
    $tasks = self::listTasksQuery([3]);
    
    //die(var_dump($tasks->toSql()));
    
    return $this->view->render($response, 'admin/task/list.html', [
      'tasks' => $tasks->get(),
    ]);
  }   
  
  public function doneTasks($request, $response, $arguments)
  {
    $tasks = self::listTasksQuery([1]);
    
    //die(var_dump($tasks->toSql()));
    
    return $this->view->render($response, 'admin/task/list.html', [
      'tasks' => $tasks->get(),
    ]);
  } 
  
  public function add($request, $response, $arguments)
  {
    $view_data = ['data' => [], 'new' => true];
    if(isset($arguments['uid'])) $view_data['data']['user_id'] = $arguments['uid'];
    
    $this->container->view->getEnvironment()->addGlobal('current', $view_data);
    
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
    return $response->withRedirect($this->router->pathFor('admin.tasks'));
  }  
  
  public function edit($request, $response, $arguments)
  {
    $taskData = Task::where('id', $arguments['uid'])->first();
    
    if(!$taskData) {
      $this->flash->addMessage('error', "No Task with ID '{$arguments['uid']}' found.");
      return $response->withRedirect($this->router->pathFor('admin.tasks'));     
    }

    $this->container->view->getEnvironment()->addGlobal('current', [
      'data' => $taskData,
    ]);
    
    return $this->view->render($response, 'admin/task/edit.html', [
      'users' => User::query()->orderBy('displayname')->get()
    ]);
  }
  
  public function postEdit($request, $response, $arguments)
  {
    $task = Task::where('id', $arguments['uid'])->first();
    
    if(!$task) {
      $this->flash->addMessage('error', "No task with ID '{$arguments['uid']}' found.");
      return $response->withRedirect($this->router->pathFor('admin.tasks'));     
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
      $this->flash->addMessage('success', "Task details for '{$credentials['title']}' have been changed.");
    } else {
      $this->flash->addMessage('error', "The task could not be updated.");
    }
    return $response->withRedirect($this->router->pathFor('admin.tasks'));
  }  
  
  public function delete($request, $response, $arguments)
  {
    $task = Task::where('id', $arguments['uid']);
    $task->delete();

    $this->flash->addMessage('success', "Task has been deleted.");
    return $response->withRedirect($this->router->pathFor('admin.tasks'));
  }  
  
  public function todo($request, $response, $arguments)
  {
    return $this->view->render($response, 'admin/task/todo.html');
  }
  
  private function updateTaskStatus($status, $request, $response, $arguments) {
    $task = Task::where('id', $arguments['uid'])->first();
    
    if(!$task) {
      $this->flash->addMessage('error', "No task with ID '{$arguments['uid']}' found.");
      return false;     
    }    
    $credentials = [ 'status' => $status ];
    
    // update data
    return $task->update($credentials);
  }
  
  public function startTask($request, $response, $arguments)
  {
    if(self::updateTaskStatus(2, $request, $response, $arguments)) 
      $this->flash->addMessage('success', "Task has been marked as started.");
    return $response->withRedirect($this->router->pathFor('admin.tasks')); 
  }
  
  public function completeTask($request, $response, $arguments)
  {
   if(self::updateTaskStatus(1, $request, $response, $arguments)) 
      $this->flash->addMessage('success', "Task has been marked as completed.");
    return $response->withRedirect($this->router->pathFor('admin.tasks')); 
  }
  
  public function blockedTask($request, $response, $arguments)
  {
    if(self::updateTaskStatus(3, $request, $response, $arguments)) 
      $this->flash->addMessage('error', "Task has been marked as blocked.");
    return $response->withRedirect($this->router->pathFor('admin.tasks')); 
  }  
}