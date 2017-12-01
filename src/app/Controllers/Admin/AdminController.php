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
    $users = User::all();
    $user_status = [];
    foreach ($users as $user) {
      $role_name = $user->roles()->first()->name;
      $user_status[$role_name] = isset($user_status[$role_name]) ? $user_status[$role_name] + 1 : 1;
    }
    
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
