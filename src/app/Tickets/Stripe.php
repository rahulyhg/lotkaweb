<?php
namespace App\Tickets;

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\Environment;

class Stripe
{  
  private $db;
  private $container;
  private $logger;
  private $json_provider;
  
  function __construct($container) {
    $this->db = $container['db'];
    $this->logger = $container['logger'];
    $this->json_provider = $container['json_provider'];
    $this->container = $container;
  }

  private function createCustomer($ticket_data, $metadata) {
    return \Stripe\Customer::create(array(
      'email'    => $ticket_data['email'],
      'source'   => $ticket_data['token'],
      'metadata' => $metadata
    ));
  }

  private function createCharge($customer, $ticket_type, $metadata, $ticket, $ticket_data) {
    return \Stripe\Charge::create(array(
        'customer' => $customer->id,
        'amount'   => ceil($ticket_type->price * 1.029), // add fee
        'currency' => 'sek',
        'metadata' => $metadata,
        'description' => $ticket->caption,
        'receipt_email' => $ticket_data['email'],
        'statement_descriptor' => $ticket_type->statement_descriptor
      ));
  }

  public function chargeTicket($ticket_type, $metadata, $ticket_data, $response) {
    try {
      $ticket = \Stripe\Product::retrieve($ticket_type->sku);
      $customer = self::createCustomer($ticket_data, $metadata);
      $charge = self::createCharge($customer, $ticket_type, $metadata, $ticket, $ticket_data);

      if ($charge->status == "succeeded") {        
        $this->logger
          ->info("Successfully charged {$ticket_data['email']} for ticket type: {$ticket_data['ticket_type']}");
        
        $order_id = $this->db
          ->table('orders')
          ->insertGetId([
            'name' => $ticket_data['surname'], 
            'email' => $ticket_data['email'],
            'type' => $ticket_type->sku,
            'amount' => $ticket_type->price,
            'size' => $ticket_data['size'],
            'preference' => $ticket_data['pref']
          ]);

        $res = $this->db
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
          return $this->json_provider->withOk(
            $response, 
            array(), 
            'Stripe Charge successful.'
          );
        } else {
          $this->logger
            ->info("Failed to charge {$ticket_data['email']} for ticket type: {$ticket_data['ticket_type']}");          
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
        return $this->json_provider->withError(
          $response, 
          "Something went wrong securing your ticket.", 
          500, 
          $body
        );
    }
  }
}
