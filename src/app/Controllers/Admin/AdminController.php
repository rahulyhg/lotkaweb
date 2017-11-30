<?php

namespace App\Controllers\Admin;

use App\Models\User;
use App\Models\Order;
use App\Controllers\Controller;
use Slim\Views\Twig as View;

class AdminController extends Controller
{
  public function index($request, $response)
  {    
    $DB = $this->db->getDatabaseManager();
    
    # Order status
    $attested_status_counts = Order::query()
      ->select(
        $DB->raw("(case when attested_id IS NOT NULL then 'attested' else 'unattested' end) as attestement_status"),
        $DB->raw("count(case when attested_id IS NOT NULL then 1 else 0 end) as num")
      )
      ->groupBy('attestement_status')
      ->get();
    
    $order_status = [];
    foreach( $attested_status_counts as $status ) {
      $order_status[$status->attestement_status] = $status->num;
    }
    
    # User status
    /* TODO
    $attested_status_counts = User::query()
      ->select(
        $DB->raw("(case when attested_id IS NOT NULL then 'attested' else 'unattested' end) as attestement_status"),
        $DB->raw("count(case when attested_id IS NOT NULL then 1 else 0 end) as num")
      )
      ->groupBy('attestement_status')
      ->get();
    
    $user_status = [];
    foreach( $attested_status_counts as $status ) {
      $order_status[$status->attestement_status] = $status->num;
    }
    */
    
    $user_status = [
      'participants' => 0,
      'users' => 0,
      'admin' => 100
    ];
    
    $ticket_sales = Order::query()
      ->select(
        $DB->raw("DATE(orderdate) AS OrderDay"),
        'type as TicketType',
        $DB->raw("COUNT(*) AS Tickets")
      )
      ->groupBy('TicketType', 'OrderDay')
      ->orderBy('OrderDay');
    
    return $this->view->render($response, 'admin/dashboard/main.twig', [
      'orderStatus' => $order_status,
      'userStatus' => $user_status,
      'sales' => $ticket_sales->get(),
    ]);
  }

  public function todo($request, $response)
  {
    
  }
}
