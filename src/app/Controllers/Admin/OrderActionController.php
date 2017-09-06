<?php
namespace App\Controllers\Admin;

use App\Models\Order;
use App\Models\Shirt;
use App\Models\Ticket;
use App\Models\Team;
use App\Models\Surname;
use App\Models\User;
use App\Controllers\Controller;
use Slim\Views\Twig as View;

class OrderActionController extends Controller
{
  private function listOrdersQuery() {
    $DB = $this->db->getDatabaseManager();
    
    return Order::query()
      ->leftJoin('users as attestors', 'orders.attested_id', '=', 'attestors.id')
      ->leftJoin('users', 'orders.user_id', '=', 'users.id')
      ->select(
        'orders.*',
        $DB->raw("IFNULL(NULLIF(users.displayname,''), users.username) as user_name"),
        $DB->raw("IFNULL(attestors.displayname, attestors.username) as attester_name")
      );
  }
  
  private function orderData() {
    return [
      'sizes' => Shirt::select('size')->distinct('size')->orderBy('id')->get(),
      'types' => Ticket::query()->where('available', 1)->distinct('sku')->orderBy('sku')->get(),
      'shirt_types' => Shirt::select('type', 'type_class')->where('available', 1)->distinct('type')->get(),
      'teams' => Team::query()->where('available', 1)->distinct('type')->orderBy('id')->get(),
      'users' => User::query()->orderBy('displayname')->get(),
      'surnames' => Surname::query()->where('available', 1)->get(),
    ];
  }

  private function orderPostData($request) {
    $data = [
      'email' => $request->getParam('email'), 
      'type' => $request->getParam('type'), 
      'amount' => $request->getParam('amount') * 100, 
      'name' => $request->getParam('name'), 
      'shirt_type' => $request->getParam('shirt_type'), 
      'size' => $request->getParam('size'), 
      'preference' => $request->getParam('preference'), 
      'user_id' => $request->getParam('user_id'), 
      'attested_id' => $request->getParam('attested_id'), 
      'orderdate' => $request->getParam('orderdate'), 
      'origin' => $request->getParam('origin'), 
    ];
    
    return array_map(function ($v) { 
      return $v === "" ? NULL : $v;
    }, $data);
  }  
  
  private function renderList($response, $data) {
    return $this->view->render($response, 'admin/order/list.html', $data);
  }
  
  //List Orders
  public function index($request, $response)
  {
    $orders_query = self::listOrdersQuery();
    
    return self::renderList($response, [
      'listOrdes' => $orders_query->get(),
    ]);
  }

  public function listAttested($request, $response)
  {
    $orders_query = self::listOrdersQuery();
    $orders_query->whereNotNull('attested_id');
    
    return self::renderList($response, [
      'listOrdes' => $orders_query->get(),
    ]);
  }   
  
  public function listUnattested($request, $response)
  {
    $orders_query = self::listOrdersQuery();
    $orders_query->whereNull('attested_id');
    
    return self::renderList($response, [
      'listOrdes' => $orders_query->get(),
    ]);
  }   
  
  //Delete Order
  public function deleteOrder($request, $response, $arguments)
  {
    $order = Order::where('id', $arguments['uid']);
    $order->delete();

    $this->flash->addMessage('success', "Order has been deleted.");
    return $response->withRedirect($this->router->pathFor('admin.orders.all'));
  }

  //Edit Order
  public function editOrder($request, $response, $arguments)
  {
    $order = Order::where('id', $arguments['uid'])->first();

    $this->container->view->getEnvironment()->addGlobal('order', self::orderData());    
    $this->container->view->getEnvironment()->addGlobal('current', [
      'data' => $order,
    ]);

    return $this->view->render($response, 'admin/order/edit.html');
  }

  public function postEditOrder($request, $response, $arguments)
  {
    $order = Order::where('id', $arguments['uid'])->first();
    $credentials = self::orderPostData($request);

    // update data
    if($order->update($credentials)) {
      $this->flash->addMessage('success', "Order details have been changed.");
    } else {
      $this->flash->addMessage('error', "Order could not be updated.");
    }
    return $response->withRedirect($this->router->pathFor('admin.orders.all'));
  }
  
  //Order attesting
  public function attestOrder($request, $response, $arguments)
  {
    $order = Order::where('id', $arguments['uid'])->first();
    
    $this->container->view->getEnvironment()->addGlobal('order', [
      'users' => User::query()->orderBy('displayname')->get(),
    ]);
    
    $this->container->view->getEnvironment()->addGlobal('current', [
      'data' => $order,
    ]);
    
    return $this->view->render($response, 'admin/order/attest.html');
  }
  
  public function postAttestOrder($request, $response, $arguments)
  {
    $order = Order::where('id', $arguments['uid'])->first();

    $credentials = [
      'user_id' => $request->getParam('user_id'), 
      'attested_id' => $this->container->sentinel->getUser()->id, 
    ];

    // update data
    if($order->update($credentials)) {
      $this->flash->addMessage('success', "Order have been attested.");
    } else {
      $this->flash->addMessage('error', "Order could not be attested.");
    }
    return $response->withRedirect($this->router->pathFor('admin.orders.all'));
  }
  
  public function unattestOrder($request, $response, $arguments)
  {
    $order = Order::where('id', $arguments['uid'])->first();

    $credentials = [
      'user_id' => null, 
      'attested_id' => null, 
    ];

    // update data
    if($order->update($credentials)) {
      $this->flash->addMessage('success', "Order have been unattested.");
    } else {
      $this->flash->addMessage('error', "Order could not be unattested.");
    }
    return $response->withRedirect($this->router->pathFor('admin.orders.all'));    
  }
  
  //Create new order
  public function addOrder($request, $response, $arguments)
  {
    $this->container->view->getEnvironment()->addGlobal('order', self::orderData()); 
    $this->container->view->getEnvironment()->addGlobal('current', [
      'data' => [],
      'new' => true,
    ]);

    return $this->view->render($response, 'admin/order/edit.html');
  }

  public function postAddOrder($request, $response, $arguments)
  {
    $credentials = self::orderPostData($request);

    // update data
    if(Order::create($credentials)) {
      $this->flash->addMessage('success', "Order details have been created.");
    } else {
      $this->flash->addMessage('error', "Order could not be created. : " . json_encode($credentials) );
    }
    return $response->withRedirect($this->router->pathFor('admin.orders.all'));
  }
}
