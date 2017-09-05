<?php
namespace App\Controllers\Admin;

use App\Models\Order;
use App\Controllers\Controller;
use Slim\Views\Twig as View;

class OrderActionController extends Controller
{
  private function listOrdes() {
    return Order::query()
      ->leftJoin('users as attestors', 'orders.attested_id', '=', 'attestors.id')
      ->leftJoin('users', 'orders.user_id', '=', 'users.id')
      ->addSelect('orders.*')
      ->addSelect('users.displayname as user_name')
      ->addSelect('attestors.displayname as attester_name');
  }
  
  private function renderList($response, $data) {
    return $this->view->render($response, 'admin/order/list.html', $data);
  }
  
  public function index($request, $response)
  {
    $orders_query = self::listOrdes();
    
    return self::renderList($response, [
      'listOrdes' => $orders_query->get(),
    ]);
  }

  public function listAttested($request, $response)
  {
    $orders_query = self::listOrdes();
    $orders_query->whereNotNull('attested_id');
    
    return self::renderList($response, [
      'listOrdes' => $orders_query->get(),
    ]);
  }   
  
  public function listUnattested($request, $response)
  {
    $orders_query = self::listOrdes();
    $orders_query->whereNull('attested_id');
    
    return self::renderList($response, [
      'listOrdes' => $orders_query->get(),
    ]);
  }   
  
  public function deleteOrder($request, $response, $arguments)
  {
    $order = Order::where('id', $arguments['uid']);
    $order->delete();

    $this->flash->addMessage('success', "Order has been deleted.");
    return $response->withRedirect($this->router->pathFor('admin.order.index'));
  }

  public function editOrder($request, $response, $arguments)
  {
    $getCurrentOrderData = Order::where('id', $arguments['uid'])->first();

    $this->container->view->getEnvironment()->addGlobal('current', [
      'data' => $getCurrentOrderData,
    ]);

    return $this->view->render($response, 'admin/order/edit.html');
  }

  public function postEditOrder($request, $response, $arguments)
  {
    $getCurrentOrderData = Order::where('id', $arguments['uid'])->first();

    $credentials = [
      'email' => $request->getParam('email'),
    ];

    // update user data
    $this->container->sentinel->update($getCurrentUserData, $credentials);

    $this->flash->addMessage('success', "Order details have been changed.");
    return $response->withRedirect($this->router->pathFor('admin.order.edit', [ 'uid' => $arguments['uid'] ]));
  }
}
