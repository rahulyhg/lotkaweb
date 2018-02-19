<?php

namespace App\Controllers\Admin\Participants;

use App\Models\Character;
use App\Models\Attribute;
use App\Models\ItemList;
use App\Models\ListItem;
use App\Models\Taxon;

use App\Controllers\Controller;
use Respect\Validation\Validator as v;
use Slim\Views\Twig as View;

class ListActionController extends Controller
{
   
  public function index($request, $response, $arguments)
  {
    $lists = ItemList::orderBy('name');
    
    //Filter by attribute
    /*
    $characters = Character::whereHas(
        'attr', function ($query) {
            $query->whereIn('name',['NPC','Costume done']);
        }
    )
    ->with('attr');
    */
    
    return $this->view->render($response, 'admin/participants/lists/list.html', [
      'lists' => $characters->get(),
    ]);
  }
  
  public function add($request, $response, $arguments)
  {
    return 'TODO: Add list';
  }
  
  public function edit($request, $response, $arguments)
  {
    return 'TODO: Edit list';
  }  

  public function save($request, $response, $arguments)
  {
    return 'TODO: Save list';
  }
  
  public function delete($request, $response, $arguments)
  {
    return 'TODO: Delete list';
  }
  
  public function items($request, $response, $arguments)
  {
    return 'TODO: List items';
  }  
  
  public function addItem($request, $response, $arguments)
  {
    return 'TODO: Add item';
  }
  
  public function editItem($request, $response, $arguments)
  {
    return 'TODO: Edit item';
  }  

  public function saveItem($request, $response, $arguments)
  {
    return 'TODO: Save item';
  }
  
  public function deleteItem($request, $response, $arguments)
  {
    return 'TODO: Delete item';
  }
  
  public function addUpdateTaxons($request, $response, $arguments)
  {
    echo '
<script src="//code.jquery.com/jquery-3.1.1.min.js" integrity="sha256-hVVnYaiADRTO2PzUGmuLJr8BLUSjGIZsDYGmIJLv2b8=" crossorigin="anonymous"></script>';
    
    if(!isset($arguments['name'])) return "No category supplied";
      
    $data = [ 
      'name' => $arguments['name'], 
      'code' => hash('crc32', $arguments['name'], false) 
    ];
    $taxon = Taxon::firstOrCreate($data);

    $parent = false;
    $parent_name = "";
    if(isset($arguments['parent'])) {
      $parent_name = $arguments['parent'];
      $parent = Taxon::firstOrCreate([ 
        'name' => $parent_name,
        'code' => hash('crc32', $parent_name, false)
      ]);
      
      $parent->subcategories()->save($taxon);
    }
    
    return $data['name'] . " added" . ($parent ? " to category $parent_name." : ".");
  }  
}
