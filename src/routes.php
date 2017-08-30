<?php
// Routes
$stripe_settings = $container->get('settings')['stripe'];
\Stripe\Stripe::setApiKey($stripe_settings['SECRET_KEY']);
\Stripe\Stripe::setApiVersion($stripe_settings['API_VERSION']);

/*
|
| ROUTES
| TODO: Clean this up and move into external classes.
|
*/

$app->get('/', function ($request, $response, $args) {
    // Sample log message
    $this->logger->info("Slim-Skeleton '/' route");

    $ticket_types = $this
      ->get('db')
      ->table('tickets')
      ->select('sku','price','description','img')
      ->where('available', '=', 1)
      ->orderBy('weight')
      ->get();
  
    $shirts = $this
      ->get('db')
      ->table('shirts')
      ->select('type', 'type_class', 'size')
      ->where('available', '=', 1)
      ->get();

    $shirt_styles = $this
      ->get('db')
      ->table('shirts')
      ->select('type', 'type_class')
      ->where('available', '=', 1)
      ->groupBy('type')
      ->get();

    $teams = $this
      ->get('db')
      ->table('teams')
      ->select('type', 'name')
      ->where('available', '=', 1)
      ->get();

    $surnames = $this
      ->get('db')
      ->table('surnames')
      ->select('surname')
      ->where('available', '=', 1)
      ->where('order_id', '=', 0)
      ->get();

    $shirt_sizes =  $this
      ->get('db')
      ->table('shirts')
      ->select('id','size')
      ->groupBy('size')
      ->orderBy('id')
      ->get();

    return $this->view->render($response, 'open/index.html', [
      'PUBLIC_KEY' => $this->get('settings')['stripe']['PUBLIC_KEY'],
      'surnames' => $surnames,
      'shirt_styles' => $shirt_styles,
      'shirt_sizes' => $shirt_sizes,
      'shirts' => $shirts,
      'teams' => $teams,
      'ticket_types' => $ticket_types
    ]);
});

$app->post('/charge', function ($request, $response, $args = array('name' => '')) {
    // Sample log message

    $data = $request->getParsedBody();
    $ticket_data = [];
    $ticket_data['token'] =         filter_var($data['token'], FILTER_SANITIZE_STRING);
    $ticket_data['email'] =         filter_var($data['email'], FILTER_SANITIZE_EMAIL);
    $ticket_data['surname'] =       filter_var($data['surname'], FILTER_SANITIZE_STRING);
    $ticket_data['type'] =          filter_var($data['type'], FILTER_SANITIZE_STRING);
    $ticket_data['size'] =          filter_var($data['size'], FILTER_SANITIZE_STRING);
    $ticket_data['pref'] =          filter_var($data['pref'], FILTER_SANITIZE_STRING);
    $ticket_data['ticket_type'] =   filter_var($data['ticket_type'], FILTER_SANITIZE_STRING);

    $this->logger
      ->info("Create Stripe Charge for {$ticket_data['email']}, ticket type: {$ticket_data['ticket_type']}");

    $metadata = array(
      'shirt_type' => $ticket_data['type'],
      'shirt_size' => $ticket_data['size'],
      'preference' => $ticket_data['pref'],
      'surname' => $ticket_data['surname']
    );

    $ticket_type = $this
      ->get('db')
      ->table('tickets')
      ->select('sku','price','description','statement_descriptor')
      ->where('available', '=', 1)
      ->where('sku', 'like', $ticket_data['ticket_type'])
      ->first();

    if(!$ticket_type) {
      return $this->json_provider->withError($response, 
        "No such ticket type available.", 500);
    }
  
  try {
    $ticket = \Stripe\Product::retrieve($ticket_type->sku);

    $customer = \Stripe\Customer::create(array(
      'email'    => $ticket_data['email'],
      'source'   => $ticket_data['token'],
      'metadata' => $metadata
    ));
    
/*
    //If we're using orders instead of sirect charge, this will add overhead.
    $order_data = array(
      "currency" => "sek",
      "customer" => $customer->id,    
      "items" => array(
        array(
          "amount" => $ticket_type->price,
          "currency" => "sek",
          "description" => $ticket->caption,
          "parent" => $ticket->id,
          "quantity" => 1
        )
      ),
      "metadata" => $metadata
    );

    $order = \Stripe\Order::create($order_data);
    $order_payment = \Stripe\Order::retrieve($order->id);
    $order_payment->pay(array("source" => $token));
*/

    $charge = \Stripe\Charge::create(array(
      'customer' => $customer->id,
      'amount'   => $ticket_type->price,
      'currency' => 'sek',
      'metadata' => $metadata,
      //      'source' => $token,
      'description' => $ticket->caption,
      'receipt_email' => $ticket_data['email'],
      'statement_descriptor' => $ticket_type->statement_descriptor
    ));

    if ($charge->status == "succeeded") {
      $order_id = $this
        ->get('db')
        ->table('orders')
        ->insertGetId([
          'name' => $ticket_data['surname'], 
          'email' => $ticket_data['email'],
          'type' => $ticket_type->sku,
          'amount' => $ticket_type->price,
          'size' => $ticket_data['size'],
          'preference' => $ticket_data['pref']
        ]);

      $res = $this
        ->get('db')
        ->table('surnames')
        ->where('surname', $ticket_data['surname'])
        ->where('available', true)
        ->where('order_id', 0)
        ->update([
          'available' => false, 
          'order_id' => $order_id
        ]);

      if($order_id) {
        //Removing charge object echo $charge;
        return $this->json_provider->withOk($response, array(), 'Stripe Charge successful.');
      } else {
        $body = "Your order could not be saved internally and was not charged.";
      }
    }

    } catch(\Stripe\Error\Card $e) {
        // Since it's a decline, \Stripe\Error\Card will be caught
        $body = $e->getJsonBody();

    } catch (\Stripe\Error\RateLimit $e) {
        // Too many requests made to the API too quickly
        $body = $e->getJsonBody();

    } catch (\Stripe\Error\InvalidRequest $e) {
        // Invalid parameters were supplied to Stripe's API
        $body = $e->getJsonBody();

    } catch (\Stripe\Error\Authentication $e) {
        // Authentication with Stripe's API failed
        // (maybe you changed API keys recently)
        $body = $e->getJsonBody();

    } catch (\Stripe\Error\ApiConnection $e) {
        // Network communication with Stripe failed
        $body = $e->getJsonBody();

    } catch (\Stripe\Error\Base $e) {
        // Display a very generic error to the user, and maybe send
        // yourself an email
        $body = $e->getJsonBody();

    } catch (Exception $e) {
        // Something else happened, completely unrelated to Stripe
        $body = $e->getMessage();

    }

    if(isset($body)) {
        return $this->json_provider->withError($response, "Something went wrong securing your ticket.", 500, $body);
    }
});

$app->group('/api/v1', function () {
  $this->get('/names', function ($request, $response, $args) {
    //$names = \App\Controllers\API::getNames();
    $names = $this
      ->get('db')
      ->table('surnames')
      ->select('surname')
      ->where('available', '=', 1)
      ->where('order_id', '=', 0)
      ->get();

    return $this->json_provider->withOk($response, ['surnames' => $names], "Unreserved names as of: ". date("Y-m-d H:i:s"));
  });

  $this->get('/reserve/{name}', function ($request, $response, $args) {
    $name = filter_var($args['name'], FILTER_SANITIZE_STRING);

    $res = $this
      ->get('db')
      ->table('surnames')
      ->where('surname', $name)
      ->where('available', true)
      ->where('order_id', 0)
      ->update([
        'available' => false
      ]);

    if ($res) {
      return $this->json_provider
        ->withOk($response, 
                 ['surnames' => $name], 
                 "Surname reserved on ". date("Y-m-d H:i:s")
                );
    } else {
      return $this->json_provider
        ->withError($response, 
                    "Surname could not be reserved. It either does not exist or is not available.", 
                    400, 
                    ['surname' => $name]
                   );
    }

  });

  $this->get('/release/{name}', function ($request, $response, $args) {
    $name = filter_var($args['name'], FILTER_SANITIZE_STRING);

    $res = $this
      ->get('db')
      ->table('surnames')
      ->where('surname', $name)
      ->where('available', false)
      ->where('order_id', 0)
      ->update([
        'available' => true
      ]);

    if ($res) {
      return $this->json_provider
        ->withOk($response, 
                 ['surnames' => $name], 
                 "Surname released on ". date("Y-m-d H:i:s")
                );
    } else {
      return $this->json_provider
        ->withError($response, 
                    "Surname could not be released. It either does not exist or is reserved via order.", 
                    400, 
                    ['surname' => $name]
                   );
    }
  });
  
  $this->get('/external/texttalk', function ($request, $response, $args) {

    $texttalk_settings = $this->get('settings')['external_stores']['texttalk'];

    $texttalk = new \Textalk\WebshopClient\Connection;
    
    $api = $texttalk->getInstance('default', array('webshop' => $texttalk_settings['shop_id']));
    $api->Admin->login(
        $texttalk_settings['admin']['user'], 
        $texttalk_settings['admin']['password']
      );

    $res = $api->Context->set(array("webshop" => $texttalk_settings['shop_id']));
    $res = $api->Order->list(array("items" => true), true);

    $choices = array();

    foreach ($res as $order) {
      $orders[] = $order["items"][0];
    }

    foreach($orders as $order) {
      global $choices;
      $res = $api->OrderItem->get($order, array("choices" => true));
      $item = array_values($res["choices"]);
      if ($item[2]) {
        $choices[] = $api->ArticleChoiceOption->get($item[2])["name"]["en"];
      }
    }

    return $this->json_provider
      ->withOk($response, $choices, "Names available at external provider: TextTalk");
  });
});
