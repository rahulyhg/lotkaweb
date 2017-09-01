<?php
namespace App\API;

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\Environment;

class Names
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
  
  public function get($all = false) {
    $query = $this->db
      ->table('surnames')
      ->select('surname');

    if(!$all) {
      $query
        ->where('available', '=', 1)
        ->where('order_id', '=', 0);
    }

    return $query->get();
  }

  public function reserve($names) {
    $nameList = is_array($names) ? $names : [$names];
    
    $query = $this->db
      ->table('surnames')
      ->whereIn('surname', $nameList)
      ->where('available', 1)
      ->where('order_id', 0);

    return $query->update(['available' => 0]);
  }
  
  public function release($names) {
    $nameList = is_array($names) ? $names : [$names];
    
    $query = $this->db
      ->table('surnames')
      ->whereIn('surname', $nameList)
      ->where('available', 0)
      ->where('order_id', 0);

    return $query->update(['available' => 1]);
  }
  
  public function updateFromTextTalk($settings) {
    $texttalk = new \Textalk\WebshopClient\Connection;
    
    $api = $texttalk->getInstance('default', array('webshop' => $settings['shop_id']));
    $api->Admin->login(
        $settings['admin']['user'], 
        $settings['admin']['password']
      );

    $res = $api->Context->set(array("webshop" => $settings['shop_id']));
    $res = $api->Order->list(array("items" => true), true);

    $choices = array();

    foreach ($res as $order) {
      $orders[] = $order["items"][0];
    }

    foreach($orders as $order) {
      global $choices;
      $res = $api->OrderItem->get($order, array("choices" => true));
      $item = array_values($res["choices"]);
      if (isset($item[2])) {
        $choices[] = $api->ArticleChoiceOption->get($item[2])["name"]["en"];
      }
    }
    
    self::reserve($choices);
    
    return $choices;
  }
}
