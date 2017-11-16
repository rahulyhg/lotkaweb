<?php

namespace App\Controllers\Admin;

use App\Models\Post;
use App\Models\Attribute;
use App\Models\Category;
use App\Models\User;
use App\Controllers\Controller;
use Respect\Validation\Validator as v;
use Slim\Views\Twig as View;

class PostActionController extends Controller
{
  private function slugify($string, $us = "-") {
    return strtolower(preg_replace("/[^A-Za-z0-9-]+/", $us, $string));  
  }
  
  private function handlePostData($request) {
    $credentials = [
      'title' => $request->getParam('title'),
      'headline' => $request->getParam('headline'),
      'description' => $request->getParam('description'),
      'publish_at' => $request->getParam('publish_at'),
      'unpublish_at' => $request->getParam('unpublish_at'),
      'visible_to' => $request->getParam('visible_to'),
      'content' => $request->getParam('content'),
      'note' => $request->getParam('note'),
      'user_id' =>  $this->container->get('view')->getEnvironment()->getGlobals()['auth']['user']->id,
    ];   
    
    $attributes = [ 'keys' => $request->getParam('attrKey'), 'values' => $request->getParam('attrVal')];
    $attribute_ids = [];
    
    foreach ($attributes['keys'] as $i => $attr_key) {
      $attribute_ids[] = Attribute::firstOrCreate(['name' => $attr_key, 'value' => $attributes['values'][$i]])->id;
    }
    
    $credentials['slug'] = self::slugify($request->getParam('slug') ? 
      $request->getParam('slug') : $request->getParam('title'));
    
    if($request->getParam('weight')) {
      $credentials['weight'] = $request->getParam('weight');
    }
    
    if($request->getParam('image')) {
      $credentials['image'] = $request->getParam('image');
    }
    
    $credentials['post_id'] = (int)$request->getParam('post_id') != 0 ? 
      $request->getParam('post_id') : null;
    
    $category_id = null;
    if($request->getParam('new_category')) {
      $category = $request->getParam('new_category');
      $category_id = Category::create(['name' => $category, 'slug' => self::slugify($category)])->id;
    } else if($request->getParam('category_id')) {
      $category = $request->getParam('category_id');
      $category_id = (int)$category;
    }
        
    $credentials['category_id'] = $category_id;
    
    return [$credentials, $attribute_ids];
  }
  
  public function index($request, $response, $arguments)
  {
    $posts = Post::orderBy('category_id', 'weight', 'title')->get();
    
    return $this->view->render($response, 'admin/post/list.html', [
      'posts' => $posts
    ]);
  }
  
  public function add($request, $response, $arguments)
  {
    $this->container->view->getEnvironment()->addGlobal('current', [
      'data' => [],
      'new' => true
    ]);
    
    return $this->view->render($response, 'admin/post/edit.html', [
      'categories' => Category::orderBy('name')->get(),      
      'posts' => Post::orderBy('title')->groupBy('category_id')->get(),
    ]);
  }
  
  public function postAdd($request, $response, $arguments)
  {    
    // update data
    $requestData = self::handlePostData($request);
    $post = Post::create($requestData[0]);
    
    // update data
    if($post->id) {
      $post->attr()->sync($requestData[1]);
      $this->flash->addMessage('success', "Post details have been saved.");
    } else {
      $this->flash->addMessage('error', "The post could not be saved.");
    }
    
    if( $request->getParam('selfsave') == 1 ) {
      return $response->withRedirect($this->router->pathFor('admin.post.edit', ['uid' => $arguments['uid']]) . "#saved");
    } else {
      return $response->withRedirect($this->router->pathFor('admin.posts.all'));
    }
  }
  
  public function edit($request, $response, $arguments)
  {
     $this->container->view->getEnvironment()->addGlobal('current', [
      'data' => Post::where('id', $arguments['uid'])->first(),
    ]);
    
    return $this->view->render($response, 'admin/post/edit.html', [
      'categories' => Category::orderBy('name')->get(),      
      'posts' => Post::orderBy('title')->groupBy('category_id')->get(),
    ]);
  }
  
  public function postEdit($request, $response, $arguments)
  {
    $post = Post::where('id', $arguments['uid'])->first();
    $requestData = self::handlePostData($request);
    
    // update data
    if($post->update($requestData[0])) {
      $post->attr()->sync($requestData[1]);
      $this->flash->addMessage('success', "Post details have been saved.");
    } else {
      $this->flash->addMessage('error', "The post could not be saved.");
    }
    
    if( $request->getParam('selfsave') == 1 ) {
      return $response->withRedirect($this->router->pathFor('admin.post.edit', ['uid' => $arguments['uid']]) . "#saved");
    } else {
      return $response->withRedirect($this->router->pathFor('admin.posts.all'));
    }
  }
  
  public function publish($request, $response, $arguments)
  {
    $post = Post::where('id', $arguments['uid'])->first();
    
    // update data
    if($post && $post->update(['publish_at' => date("Y-m-d H:i:s"), 'unpublish_at' => null])) {
      $this->flash->addMessage('success', "The post has been published.");
    } else {
      $this->flash->addMessage('error', "The post could not be published.");
    }
    return $response->withRedirect($this->router->pathFor('admin.posts.all'));    
  }
  
  public function unpublish($request, $response, $arguments)
  {
    $post = Post::where('id', $arguments['uid'])->first();
    
    // update data
    if($post && $post->update(['unpublish_at' => date("Y-m-d H:i:s")])) {
      $this->flash->addMessage('success', "The post has been unpublished.");
    } else {
      $this->flash->addMessage('error', "The post could not be unpublished.");
    }
    return $response->withRedirect($this->router->pathFor('admin.posts.all'));    
  }  
  
  public function delete($request, $response, $arguments)
  {
    $item = Post::where('id', $arguments['uid'])->first();
    
    $item->attr()->sync([]);

    $item->delete();
    $this->flash->addMessage('warning', "{$item->title} was deleted.");
    return $response->withRedirect($this->router->pathFor('admin.posts.all'));
  }
}