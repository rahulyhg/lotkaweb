<?php

namespace App\Controllers\Admin;

use App\Models\User;
use App\Models\Order;
use App\Models\Character;
use App\Controllers\Controller;
use Slim\Views\Twig as View;

class AdminController extends Controller
{
  
  private function getAge($dob) {
    if(count($dob['errors'])) return false;
    $then = $dob['year'] . 
            str_pad($dob['month'], 2, '0', STR_PAD_LEFT) . 
            str_pad($dob['day'], 2, '0', STR_PAD_LEFT);      
    $then = date('Ymd', strtotime($then));
    $diff = date('Ymd') - $then;
    return substr($diff, 0, -4);
  }
  
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
    $user_roles = [];
    $user_countries = [];
    $user_genders = [];
    $user_ages = [];
    $casting_status = [];
    
    foreach ($users as $user) {
      $role_name = $user->roles()->first()->name;
      $user_roles[$role_name] = isset($user_roles[$role_name]) ? 
        $user_roles[$role_name] + 1 : 1;
      
      $country_name = $user->attr->where('name', 'country')->first();
      if($country_name) {
        $country_name = strlen($country_name->value) ? $country_name->value : "[NOT SET]";
        $user_countries[$country_name] = isset($user_countries[$country_name]) ? 
          $user_countries[$country_name] + 1 : 1;          
      }
      
      $gender_name = $user->attr->where('name', 'gender')->first();
      if($gender_name) {
        $gender_name = strlen($gender_name->value) ? $gender_name->value : "[NOT SET]";
        $user_genders[$gender_name] = isset($user_genders[$gender_name]) ? 
          $user_genders[$gender_name] + 1 : 1;
      }
      
      if($user->character_id != 0 && !is_null($user->character_id)) {
        $casting_status['casted'] = isset($casting_status['casted']) ? 
          $casting_status['casted'] + 1 : 1;
      } else {
        $casting_status['not_casted'] = isset($casting_status['not_casted']) ? 
          $casting_status['not_casted'] + 1 : 1;
      }
  
      $birth_date = $user->attr->where('name', 'birth_date')->first();
      $id_nr = $user->attr->where('name', 'id_number_swe')->first();      
      
      $dob = false;
      if($birth_date) {
        $dob = date_parse($birth_date->value);
      } else if ( $id_nr ) {
        preg_match_all("/\d{1,2}/", substr($id_nr->value,0,6), $date_parts);
        $dob = date_parse(implode("-", $date_parts[0]));
        unset($date_parts);
      }

      if($dob) {
        $age = self::getAge($dob);
                
        if($age && $age > 0) {
          $user_ages[$age] = isset($user_ages[$age]) ? 
            $user_ages[$age] + 1 : 1;          
        }
      }
      
    }
    
    $characters = Character::all();
    $review_status = [];
    
    foreach ($characters as $character) {
      $sent_to_review = $character->attr->where('name', 'submitted_for_review')->first();      
      if($sent_to_review) {
        if($character->attr->where('name', 'reviewed')->first()) {
          $review_status['reviewed'] = isset($review_status['reviewed']) ? 
            $review_status['reviewed'] + 1 : 1;
        } else {
          $review_status['submitted'] = isset($review_status['submitted']) ? 
            $review_status['submitted'] + 1 : 1;
        }
      } else {
        $review_status['not_submitted'] = isset($review_status['not_submitted']) ? 
          $review_status['not_submitted'] + 1 : 1;
      }
      
      
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
      'castedPlayers' => $casting_status,
      'reviewStatus' => $review_status,
      'orderStatus' => $order_status,
      'userRoles' => $user_roles,
      'userCountries' => $user_countries,
      'userGenders' => $user_genders,
      'userAges' => $user_ages,
      'sales' => $ticket_sales->get(),
    ]);
  }

  public function todo($request, $response)
  {
    
  }
}
