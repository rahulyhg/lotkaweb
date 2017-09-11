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

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\Environment;

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
      'shirt_lookup' => [
        'Regular Fit T-Shirt' => 'REGULAR_TSHIRT',
        'Normal fit' => 'REGULAR_TSHIRT',
        'REGULAR_TSHIR' => 'REGULAR_TSHIRT',
        'Slim T-shirt' => 'SLIM_TSHIRT',
        'Slim fit' => 'SLIM_TSHIRT',
        'SLIM_TSHIRT' => 'SLIM_TSHIRT',
      ],
      'team_lookup' => [
        'MAINT' => 'MAINT',
        'Maintenance' => 'MAINT',
        'SURFOPS' => 'SURFOPS',
        'Surface Operations' => 'SURFOPS',
        'COMMAND' => 'COMMAND',
        'Outpost Command' => 'COMMAND',
        'MISCON' => 'MISCON' ,
        'Mission Control' => 'MISCON',
        'OTHER' => 'OTHER',
        'Other' => 'OTHER',
      ],
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
    $orders_query->orderBy('id', 'desc');
    
    return self::renderList($response, [
      'listOrdes' => $orders_query->get(),
    ]);
  }

  public function listAttested($request, $response)
  {
    $orders_query = self::listOrdersQuery();
    $orders_query->whereNotNull('attested_id')
      ->orderBy('id', 'desc');
    
    return self::renderList($response, [
      'listOrdes' => $orders_query->get(),
    ]);
  }   
  
  public function listUnattested($request, $response)
  {
    $orders_query = self::listOrdersQuery();
    $orders_query->whereNull('attested_id')
      ->orderBy('id', 'desc');
    
    return self::renderList($response, [
      'listOrdes' => $orders_query->get(),
    ]);
  }   
  
  public function listPartialPayments($request, $response)
  {
    $orders_query = self::listOrdersQuery();
    $orders_query->whereIn('type', ['STD_1','STD_2','STD_3'])
      ->orderBy('email');
    
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

  public function extrenalDashboard($request, $response, $arguments) 
  {
    return $this->view->render($response, 'admin/order/external/index.html');
  }
    
  //Handle external order platforms
  public function extrenalStripe($request, $response, $arguments) 
  {
    $stripe_orders = \Stripe\Charge::all(array("limit" => 100));
    
    //Making a few quick lookup objects
    $stripe_products = \Stripe\Product::all(array("limit" => 100));
    $products = [];
    foreach ($stripe_products->data as $product) {
      $products[$product['caption']] = $product;
    }
    
    $existing_orders = Order::query()->select('id', 'type', 'email', 'amount')->get();
    $orders = [];
    foreach ($existing_orders as $order) {
      $orders[$order->type . $order->email . $order->amount] = $order->id;
    }
    
    $this->container->view->getEnvironment()->addGlobal('orders', $stripe_orders);
    $this->container->view->getEnvironment()->addGlobal('products', $products);
    $this->container->view->getEnvironment()->addGlobal('exising', $orders);
    
    return $this->view->render($response, 'admin/order/external/stripe.html');
  }
  
  public function postExtrenalStripe($request, $response, $arguments) 
  {
    $posts = $request->getParsedBody();
    $meta_data_keys = [ 
      'shirt_type' => 'shirt_type', 
      'shirt_size' => 'size', 
      'preference' => 'preference', 
      'surname' => 'name'
    ];
    $inserts = 0;
    
    foreach ($posts['import'] as $index => $lookup_key) {
      $data = [
        'amount' => $posts['amount'][$index],
        'email' => $posts['email'][$index],
        'origin' => $posts['origin'][$index],
        'type' => $posts['ticket_type'][$index],
        'orderdate' => $posts['orderdate'][$index],
      ]; 
      
      foreach ($meta_data_keys as $meta_key => $db_key) {
        if ( strlen($posts[$meta_key][$index]) ) 
          $data[$db_key] = $posts[$meta_key][$index];
      }
      
      if(Order::create($data)) $inserts++;
    }
    
    $this->flash->addMessage('success', 
      $inserts . " of " . count($posts['import']) . " Stripe orders added to the local database.");
    return $response->withRedirect($this->router->pathFor('admin.orders.external.stripe')); 
  }

  public function extrenalTextTalk($request, $response, $arguments) 
  {
    $endpoint = "https://spetsnaz.su/~tz/lvnamefetcher/orders.php?version=2";
    $texttalk_orders = json_decode(file_get_contents($endpoint));
    
    $tickets = Ticket::select('sku', 'price')->distinct('sku')->orderBy('sku')->get();
    $ticket_types = [];
    foreach ($tickets as $ticket) {
      $ticket_types[$ticket->sku] = $ticket->price;
    }        
    
    $existing_orders = Order::query()->select('id', 'type', 'email')->get();
    $orders = [];
    foreach ($existing_orders as $order) {
      $orders[$order->type . $order->email] = $order->id;
    }    
    
    $this->container->view->getEnvironment()->addGlobal('orders', $texttalk_orders->data);
    $this->container->view->getEnvironment()->addGlobal('exising', $orders);
    $this->container->view->getEnvironment()->addGlobal('ticketTypes', $ticket_types);
    
    return $this->view->render($response, 'admin/order/external/texttalk.html');
  }
  
  public function postExtrenalTextTalk($request, $response, $arguments) 
  {
    $posts = $request->getParsedBody();
    $meta_data_keys = [ 
      'shirt_type' => 'shirt_type', 
      'shirt_size' => 'size', 
      'preference' => 'preference', 
      'surname' => 'name'
    ];
    $inserts = 0;
    
    foreach ($posts['import'] as $index => $lookup_key) {
      $data = [
        'type' => $posts['ticket_type'][$index],
        'amount' => $posts['amount'][$index],
        'email' => $posts['email'][$index],
        'origin' => $posts['origin'][$index],
        'orderdate' => $posts['orderdate'][$index],
      ]; 
      
      foreach ($meta_data_keys as $meta_key => $db_key) {
        if ( strlen($posts[$meta_key][$index]) ) 
          $data[$db_key] = $posts[$meta_key][$index];
      }
      
      if(Order::create($data)) $inserts++;
    }
    
    $this->flash->addMessage('success', 
      $inserts . " of " . count($posts['import']) . " TextTalk orders added to the local database.");
    return $response->withRedirect($this->router->pathFor('admin.orders.external.texttalk')); 
  }  
}
