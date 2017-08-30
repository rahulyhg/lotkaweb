<?php
// Routes
$stripe_settings = $container->get('settings')['stripe'];
\Stripe\Stripe::setApiKey($stripe_settings['SECRET_KEY']);
\Stripe\Stripe::setApiVersion($stripe_settings['API_VERSION']);

/*
|
| ROUTES
|
*/

$app->get('/', function ($request, $response, $args) {
    // Sample log message
    //$this->logger->info("Slim-Skeleton '/' route");


// TODO: MOVE!
    $shirts = [
        ['type' => 'Slim T-shirt', 'type_class' => 'SLIM_TSHIRT', 'size' => 'XS'],
        ['type' => 'Slim T-shirt', 'type_class' => 'SLIM_TSHIRT', 'size' => 'S'],
        ['type' => 'Slim T-shirt', 'type_class' => 'SLIM_TSHIRT', 'size' => 'M'],
        ['type' => 'Slim T-shirt', 'type_class' => 'SLIM_TSHIRT', 'size' => 'L'],
        ['type' => 'Slim T-shirt', 'type_class' => 'SLIM_TSHIRT', 'size' => 'XL'],
        ['type' => 'Slim T-shirt', 'type_class' => 'SLIM_TSHIRT', 'size' => 'XXL'],
        ['type' => 'Slim T-shirt', 'type_class' => 'SLIM_TSHIRT', 'size' => 'XXXL'],
        ['type' => 'Slim T-shirt', 'type_class' => 'SLIM_TSHIRT', 'size' => 'XXXXL'],
        ['type' => 'Regular Fit T-Shirt', 'type_class' => 'REGULAR_TSHIRT', 'size' => 'XS'],
        ['type' => 'Regular Fit T-Shirt', 'type_class' => 'REGULAR_TSHIRT', 'size' => 'S'],
        ['type' => 'Regular Fit T-Shirt', 'type_class' => 'REGULAR_TSHIRT', 'size' => 'M'],
        ['type' => 'Regular Fit T-Shirt', 'type_class' => 'REGULAR_TSHIRT', 'size' => 'L'],
        ['type' => 'Regular Fit T-Shirt', 'type_class' => 'REGULAR_TSHIRT', 'size' => 'XL'],
        ['type' => 'Regular Fit T-Shirt', 'type_class' => 'REGULAR_TSHIRT', 'size' => 'XXL'],
        ['type' => 'Regular Fit T-Shirt', 'type_class' => 'REGULAR_TSHIRT', 'size' => 'XXXL'],
        ['type' => 'Regular Fit T-Shirt', 'type_class' => 'REGULAR_TSHIRT', 'size' => 'XXXXL'],
        ['type' => 'Hooie', 'type_class' => 'HOODIE', 'size' => 'XS'],
        ['type' => 'Hooie', 'type_class' => 'HOODIE', 'size' => 'S'],
        ['type' => 'Hooie', 'type_class' => 'HOODIE', 'size' => 'M'],
        ['type' => 'Hooie', 'type_class' => 'HOODIE', 'size' => 'L'],
        ['type' => 'Hooie', 'type_class' => 'HOODIE', 'size' => 'XL'],
        ['type' => 'Hooie', 'type_class' => 'HOODIE', 'size' => 'XXL'],
        ['type' => 'Hooie', 'type_class' => 'HOODIE', 'size' => 'XXXL'],
        ['type' => 'Hooie', 'type_class' => 'HOODIE', 'size' => 'XXXXL'],
    ];

    $shirt_styles = [
        'SLIM_TSHIRT' => 'Slim T-shirt',
        'REGULAR_TSHIRT' => 'Regular Fit T-Shirt',
    ];

    $teams = [
        'MAINT' => 'Maintenance',
        'SURFOPS' => 'Surface Operations',
        'COMMAND' => 'Outpost Command',
        'MISCON' => 'Mission Control',
        'OTHER' => 'Other',
    ];

    $surnames = [
        ['surname' => 'Aaron']
    ];

    $shirt_sizes = array_unique(array_map( 
        function($shirt) { return $shirt['size']; }, 
        $shirts
    ));  


    return $this->view->render($response, 'open/index.html', [
        'PUBLIC_KEY' => $this->get('settings')['stripe']['PUBLIC_KEY'],
        'surnames' => $surnames,
        'shirt_styles' => $shirt_styles,
        'shirt_sizes' => $shirt_sizes,
        'shirts' => $shirts,
        'teams' => $teams
    ]);
});

$app->post('/charge', function ($request, $response, $args = array('name' => '')) {
    // Sample log message
    //$this->logger->info("Create Slim charge");

    $ticke_prices = array(
        'SUPPORT' =>        array(
            'id' => 4,
            'price' => 360000, 
            'description' => 'Support Ticket', 
            'statement_descriptor' => 'Lotka-Volterra, Ticket'
        ),
        'STANDARD' =>   array(
            'id' => 5,
            'price' => 260000, 
            'description' => 'Standard Ticket', 
            'statement_descriptor' => 'Lotka-Volterra, Ticket'
        ),
        'STD_1' =>          array(
            'id' => 5,
            'price' => 130000, 
            'description' => 'Standard Ticket, down payment', 
            'statement_descriptor' => 'Lotka-Volterra, Ticket'
        ),
        'STD_2' =>          array(
            'id' => 5,
            'price' => 130000, 
            'description' => 'Standard Ticket, final payment', 
            'statement_descriptor' => 'Lotka-Volterra, Ticket'
        ),
        'SUBSIDIZED' => array(
            'id' => 6,
            'price' => 170000, 
            'description' => 'Subsidized Ticket', 
            'statement_descriptor' => 'Lotka-Volterra, Ticket'
        )
    );

    $data = $request->getParsedBody();
    $ticket_data = [];
    $ticket_data['token'] =         filter_var($data['token'], FILTER_SANITIZE_STRING);
    $ticket_data['email'] =         filter_var($data['email'], FILTER_SANITIZE_EMAIL);
    $ticket_data['surname'] =       filter_var($data['surname'], FILTER_SANITIZE_STRING);
    $ticket_data['type'] =          filter_var($data['type'], FILTER_SANITIZE_STRING);
    $ticket_data['size'] =          filter_var($data['size'], FILTER_SANITIZE_STRING);
    $ticket_data['pref'] =          filter_var($data['pref'], FILTER_SANITIZE_STRING);
    $ticket_data['ticket_type'] =   filter_var($data['ticket_type'], FILTER_SANITIZE_STRING);

    $metadata = array(
        'shirt_type' => $ticket_data['type'],
        'shirt_size' => $ticket_data['size'],
        'preference' => $ticket_data['pref'],
        'surname' => $ticket_data['surname']
    );

try {
    $ticket = \Stripe\Product::retrieve($ticket_data['ticket_type']);

    $customer = \Stripe\Customer::create(array(
        'email' => $ticket_data['email'],
        'source'  => $ticket_data['token'],
        'metadata' => $metadata
    ));
/*
$order_data = array(
"currency" => "sek",
"customer" => $customer->id,    
"items" => array(
array(
"amount" => $ticke_prices[$ticket->id]["price"],
"currency" => "sek",
"description" => $ticket->caption,
"parent" => $ticket->id,
"quantity" => 1
)
),
"metadata" => array(
"shirt_type" => $shirt_type,
"shirt_size" => $shirt_size,
"preference" => $preference,
"surname" => $surname
)
);

$order = \Stripe\Order::create($order_data);
$order_payment = \Stripe\Order::retrieve($order->id);
$order_payment->pay(array("source" => $token));
*/

    $charge = \Stripe\Charge::create(array(
        'customer' => $customer->id,
        'amount'   => $ticke_prices[$ticket->id]['price'],
        'currency' => 'sek',
        'metadata' => $metadata,
        //      'source' => $token,
        'description' => $ticket->caption,
        'receipt_email' => $ticket_data['email'],
        'statement_descriptor' => $ticke_prices[$ticket->id]['statement_descriptor']
    ));

    if ($charge->status == "succeeded") {
/*
|
| TODO: Remove names from database here
|
*/
/*
    $order_id = DB::table('orders')->insertGetId(
        [
            'name' => $surname, 
            'email' => $customer_email,
            'type' => $ticke_prices[$ticket->id]['id'],
            'amount' => $ticke_prices[$ticket->id]['price'],
            'size' => $shirt_size,
            'preference' => $preference
        ]);

    $res = DB::table('surnames')
        ->where('surname', $surname)
        ->where('available', true)
        ->where('order_id', 0)
        ->where('user_id', 0)
        ->update([
            'available' => false, 
            'order_id' => $order_id
        ]);
*/
        return $this->json_provider->withOk($response, $charge, 'Stripe Charge successful.');
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
        $body = $e->getJsonBody();

    }

    if(isset($body)) {
        return $this->json_provider->withError($response, "Something went wrong securing your ticket.", 500, $body);
    }

    // return error
//    return $this->json_provider->withError($response, 'Lecturer not exist', 404);

});

$app->group('/api/v1', function () {
    $this->get('/names', function ($request, $response, $args) {
        return $this->json_provider->withOk($response, ['surnames' => ['TODO: populate names']], "Unreserved names as of: ". date("Y-m-d H:i:s"));
    });

    $this->get('/reserve/{name}', function ($request, $response, $args) {
        $name = filter_var($args['name'], FILTER_SANITIZE_STRING);
        return $this->json_provider->withError($response, "Unimplemented API function: 'reserve'", 501, ['surname' => $name]);
    });

    $this->get('/release/{name}', function ($request, $response, $args) {
        $name = filter_var($args['name'], FILTER_SANITIZE_STRING);
        return $this->json_provider->withError($response, "Unimplemented API function: 'release'", 501, ['surname' => $name]);
    });    
});
