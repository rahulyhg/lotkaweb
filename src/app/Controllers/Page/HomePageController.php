<?php

namespace App\Controllers\Page;

use App\Models\Post;
use App\Models\Surname;
use App\Models\Shirt;
use App\Models\Team;
use App\Models\Ticket;

use App\Controllers\Controller;
use Slim\Views\Twig as View;

class HomePageController extends Controller
{
  private function populateTicketInfo( $ticket_filter = [['available', '=', 1],['visibility', '=', 1]], $multiples = false ) {
    
    $ticket_selection = Ticket::orderBy('weight')->where($ticket_filter);
    if ($multiples) {
      foreach ($multiples as $value) {
        $value = $value.'%';
      }
      unset($value);
      $ticket_selection = $ticket_selection->whereIn('sku', $multiples);
    }
    
    return [
      'surnames' =>     Surname::where('available', '=', 1)->get(),
      'shirt_styles' => Shirt::where('available', '=', 1)->groupBy('type')->get(),
      'shirt_sizes' =>  Shirt::orderBy('id')->groupBy('size')->get(),
      'shirts' =>       Shirt::where('available', '=', 1),
      'teams' =>        Team::where('available', '=', 1)->get(),
      'ticket_types' => $ticket_selection->get()
    ];
  }
  
  public function index($request, $response, $arguments)
  {
    $page = Post::where('slug', 'about')->first();    
    return $this->view->render($response, '/new/index.html', [
      'showSplash' => true,
      'content' => $page->content,
    ]);
  }
  
  public function page($request, $response, $arguments)
  {
    $slug = isset($arguments['page']) ? $arguments['page'] : $arguments['category'];
    $slug = filter_var($slug, FILTER_SANITIZE_STRING);
    $post = Post::where('slug', $slug)->visibleTo(['public','participant','admin'])->published()->first();

    if($slug == 'tickets') {
      $this->container->view->getEnvironment()->addGlobal(
        'tickets', self::populateTicketInfo());
    }
    
    if(!$post) 
      return  $response->withRedirect($this->router->pathFor('home'));
    
    return $this->view->render($response, '/new/page.html', [
      'PUBLIC_KEY' => $this->container->get('stripe')['PUBLIC_KEY'],
      'post' => $post,
      'slug' => $slug,
    ]);
  }
  
  public function ticket($request, $response, $arguments)
  {
    $sku = filter_var($arguments['sku'], FILTER_SANITIZE_STRING);
    $ticket_data = self::populateTicketInfo([['available', '=', 1]], explode(',', $sku));    
    $this->container->view->getEnvironment()->addGlobal(
      'tickets', $ticket_data);
    
    return $this->view->render($response, '/new/page.html', [
      'hideHead' => true,
      'PUBLIC_KEY' => $this->container->get('stripe')['PUBLIC_KEY'],
      'post' => Post::where('slug', 'single_ticket')->first(),
    ]);
  }
  
  public function devPosts($count = 2) 
  {
    $devBlogEndpoint = $this->container->get('settings')['event']['devBlog'];
    $json_data = "{}";
    
    if ($devBlogEndpoint['active']) {
      try {
        $content = file_get_contents($devBlogEndpoint['uri'] . '?json=get_recent_posts&count=' . $count);

        if ($content === false) {
        } else {
          $json_data = $content;
        }
      } catch (Exception $e) {
        // Handle exception
      }
    }    
    return json_decode($json_data);
  }

  public function press($request, $response, $arguments)
  {
    return $this->view->render($response, '/new/barebones.html', [
      'content' => Post::where('slug', 'press')->first()->content,
    ]);  
  }  
}