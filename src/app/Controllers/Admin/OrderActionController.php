<?php
namespace App\Controllers\Admin;

use Textalk\WebshopClient\Connection;
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
      'types' => Ticket::query()->distinct('sku')->orderBy('sku')->get(),
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

  public function csv($request, $response, $arguments)
  {
    $orders_query = self::listOrdersQuery();
    $orders_query->orderBy('id', 'desc');
    
    
    return $this->view->render($response, 'admin/order/csv.html', [
      'orders' => $orders_query->get(),
    ])->withHeader('Content-Type', 'text/csv');
  } 
  
  public function listMultiples($request, $response)
  {
    $DB = $this->db->getDatabaseManager();
    $multi_rows = Order::query()
                 ->select('email', $DB->raw('count(*) as `count`'))
                 ->groupBy('email')
                 ->having('count', '>', 1);
    
    $multi_emails = [];
    foreach ($multi_rows->get() as $order) {
      $multi_emails[] = $order->email;
    }

    $orders_query = self::listOrdersQuery();
    $orders_query->whereIn('orders.email', $multi_emails)      
      ->orderBy('orders.email', 'desc');
    
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
    $orders_query->whereIn('type', ['STD_1', 'SUBSIDIZED_1', 'SUBSIDIZED_2', 'STD_2', 'STD_3'])
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
  
  private function getStripeBatches($stripe_order_data = [], $last_object = false) {
    $charge_args = array("limit" => 10);
    if($last_object) $charge_args["starting_after"] = $last_object;
    
    $stripe_orders = \Stripe\Charge::all($charge_args);
    if($stripe_orders->has_more) {
      return self::getStripeBatches(array_merge($stripe_order_data, $stripe_orders->data), end($stripe_orders->data)->id);
    } else {
      return array_merge($stripe_order_data, $stripe_orders->data);
    }
  }
    
  //Handle external order platforms
  public function extrenalStripe($request, $response, $arguments) 
  {
    $stripe_orders = ["data" => self::getStripeBatches()];
    
    //Making a few quick lookup objects
    $stripe_products = \Stripe\Product::all(array("limit" => 10));
    $products = [];
    foreach ($stripe_products->data as $product) {
      $products[$product['caption']] = $product;
    }
    
    $existing_orders = Order::query()->select('id', 'type', 'email')->get();
    $orders = [];
    foreach ($existing_orders as $order) {
      $orders[$order->type . $order->email] = $order->id;
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
    return $response->withRedirect($this->router->pathFor('admin.orders.external')); 
  }

  public function extrenalTextTalk($request, $response, $arguments) 
  {
    $settings = $this->container->get('settings')['external_stores']['texttalk'];
    $api = Connection::getInstance('default', array('webshop' => $settings['shop_id']));
    $api->Admin->login(
      $settings['admin']['user'], 
      $settings['admin']['password']
    );

    $res = $api->Context->set(array("webshop" => $settings['shop_id']));
    $res = $api->Order->list(true, true);
    $texttalk_orders = [];
    
    foreach ($res as $order) {
      if ($order["discarded"]) continue;
      $res2 = $api->OrderItem->get(
        $order["items"][0], 
        array("choices" => true, "articleNumber"=>true)
      );

      $choices = [];
      /*
      $items = array_values($res2["choices"]);
      foreach ($items as $item) {
        if((int)$item == 0) continue;
        $choices[] = $api->ArticleChoiceOption->get($item)["name"]["en"];
      }
      */
      
      $payments = [];
      foreach ($order["payments"] as $item) {
        if((int)$item == 0) continue;
        $payments[] = $api->Payment->get($item);
      }

      $order["type"] = substr($res2["articleNumber"],0,5) == "LVSUP" ? "SUPPORT" : "STANDARD";
      $order["choices"] = $choices;
      $order["payments"] = $payments;
      $texttalk_orders[] = $order;
    }
    
//  $texttalk_orders["paymentSchema"] = $api->Payment->getSchema(null);
    
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
    
    $this->container->view->getEnvironment()->addGlobal('orders', $texttalk_orders);
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

    if(isset($posts['import'])) {
      
      $inserts = 0;
      $fails = [];      
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
        else $fails[] = $data;
      }

      if(count($fails)) {
        $this->flash->addMessage('debug', $fails);

        $this->flash->addMessage('error', 
          count($fails) . "TextTalk orders failed to be imported into the local database.");    
        
        die(var_dump($fails));
        
      }    

      if($inserts) {
      $this->flash->addMessage('success', 
        $inserts . " of " . count($posts['import']) . " TextTalk orders added to the local database.");
      }

    } else {
      die(var_dump($posts));
    }
    
    return $response->withRedirect($this->router->pathFor('admin.orders.external')); 
  }  
}
