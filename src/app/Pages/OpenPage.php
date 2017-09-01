<?php
namespace App\Pages;

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\Environment;

/*
| TODO: This code can be a lot prettier!
*/

class OpenPage
{  
  private $db;
  private $container;
  private $logger;
  private $json_provider;
  private $view;
  
  function __construct($container) {
    $this->db = $container['db'];
    $this->logger = $container['logger'];
    $this->view = $container['view'];
    $this->json_provider = $container['json_provider'];
    $this->container = $container;
  }
  
  public function index($request, $response, $args) {
    $shirts = $this->db
      ->table('shirts')
      ->select('type', 'type_class', 'size')
      ->where('available', '=', 1)
      ->get();

    $shirt_styles = $this->db
      ->table('shirts')
      ->select('type', 'type_class')
      ->where('available', '=', 1)
      ->groupBy('type')
      ->get();

    $teams = $this->db
      ->table('teams')
      ->select('type', 'name')
      ->where('available', '=', 1)
      ->get();

    $surnames = $this->db
      ->table('surnames')
      ->select('surname')
      ->where('available', '=', 1)
      ->where('order_id', '=', 0)
      ->get();

    $shirt_sizes = $this->db
      ->table('shirts')
      ->select('id','size')
      ->groupBy('size')
      ->orderBy('id')
      ->get();

    if($request->getAttribute('sku')) {
      $sku = filter_var($args['sku'], FILTER_SANITIZE_STRING);
      $ticket_types = $this->db
        ->table('tickets')
        ->select(
          'sku',
          'price',
          'description',
          'img', 
          'surname', 
          'shirtType', 
          'size', 
          'teamPreference'
        )
        ->where('available', '=', 1)
        ->where('sku', $sku)
        ->orderBy('weight')
        ->get();

      $this->logger->info("Get single ticket type payment page for {$sku}");
      $page = 'part_ticket.html';
    } else {
      $ticket_types = $this->db
        ->table('tickets')
        ->select(
          'sku',
          'price',
          'description',
          'img', 
          'surname', 
          'shirtType', 
          'size', 
          'teamPreference'
        )
        ->where('available', '=', 1)
        ->where('visible', '=', 1)
        ->orderBy('weight')
        ->get();

      $this->logger->info("Get '/' route");    
      $page = 'open/index.html';
    }
    
    return $this->view->render($response, $page, [
      'PUBLIC_KEY' => $this->container->get('settings')['stripe']['PUBLIC_KEY'],
      'surnames' => $surnames,
      'shirt_styles' => $shirt_styles,
      'shirt_sizes' => $shirt_sizes,
      'shirts' => $shirts,
      'teams' => $teams,
      'ticket_types' => $ticket_types
    ]);
  }
  
  public function charge($request, $response, $args) {
    $data = $request->getParsedBody();
    
    $ticket_data = [];
    if( !( 
      isset($data['token']) 
      && isset($data['email']) 
      && isset($data['surname']) 
      && isset($data['type']) 
      && isset($data['size']) 
      && isset($data['pref']) 
      && isset($data['ticket_type']) 
    ) ) {
      return $this->json_provider->withError($response, 
        "Missing one or more ticket arguments.", 500);
    }
  
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

    $ticket_type = $this->db
      ->table('tickets')
      ->select('sku','price','description','statement_descriptor')
      ->where('available', '=', 1)
      ->where('sku', 'like', $ticket_data['ticket_type'])
      ->first();

    if(!$ticket_type) {
      return $this->json_provider->withError($response, 
        "No such ticket type available.", 500);
    }
    
    $ticketProvider = new \App\Tickets\Stripe($this->container); 
    
    return $ticketProvider->chargeTicket($ticket_type, $metadata, $ticket_data, $response);
  }
}