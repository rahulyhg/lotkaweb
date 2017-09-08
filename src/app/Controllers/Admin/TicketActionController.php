<?php

namespace App\Controllers\Admin;

use App\Models\Ticket;
use App\Controllers\Controller;
use Respect\Validation\Validator as v;
use Slim\Views\Twig as View;

class TicketActionController extends Controller
{
  public function index($request, $response, $arguments)
  {
    $tickets = Ticket::select('*')->orderBy('weight')->get();
    $settings = [
      'name' => 'Tickets',
      'title' => 'Ticket Management',
      'breadcrumb' => [
//          ['name' => '', 'path' => '']
      ],
      'columns' => [
        ['title' => 'SKU', 'field_name' => 'sku', /*'class' => ''*/],
        ['title' => 'Price', 'field_name' => 'price', /*'class' => ''*/],
        ['title' => 'Description', 'field_name' => 'description', /*'class' => ''*/],
        ['title' => 'Available', 'field_name' => 'available', 'class' => 'center'],
        ['title' => 'Visible', 'field_name' => 'visibility', 'class' => 'center'],
      ],
      'actions' => [
        ['path' => 'admin.ticket.edit', 'class' => 'default', 'title' => 'Edit ticket', 'icon' => 'edit'],
      ],
    ];
    
    return $this->view->render($response, 'admin/partials/list.html', [
      'settings' => $settings,
      'list' => $tickets,
    ]);    
  }
  
  public function add($request, $response, $arguments)
  {
    
  }
  
  public function postAdd($request, $response, $arguments)
  {
    
  }
  
  public function edit($request, $response, $arguments)
  {
    $ticketData = Ticket::where('id', $arguments['uid'])->first();
    
    if(!$ticketData) {
      $this->flash->addMessage('error', "No ticket with ID '{$arguments['uid']}' found.");
      return $response->withRedirect($this->router->pathFor('admin.tickets.all'));     
    }

    $this->container->view->getEnvironment()->addGlobal('current', [
      'data' => $ticketData,
    ]);
    
    $this->container->view->getEnvironment()->addGlobal('settings', [
      'name' => 'Edit ticket',
      'title' => 'Ticket Management',
      'breadcrumb' => [
        ['name' => 'Tickets', 'path' => 'admin.tickets.all']
      ],
    ]);
    
    return $this->view->render($response, 'admin/ticket/edit.html');    
  }
  
  public function postEdit($request, $response, $arguments)
  {
    $ticket = Ticket::where('id', $arguments['uid'])->first();
    
    if(!$ticket) {
      $this->flash->addMessage('error', "No ticket with ID '{$arguments['uid']}' found.");
      return $response->withRedirect($this->router->pathFor('admin.tickets.all'));     
    }
    
    $credentials = [
      'sku' => $request->getParam('sku'),
      'description' => $request->getParam('description'),
      'statement_descriptor' => $request->getParam('statement_descriptor'),
      'price' => $request->getParam('price'),
      'image' => $request->getParam('image'),
      'available' => ( $request->getParam('available') ? true : false),
      'visibility' => ( $request->getParam('visibility') ? true : false),
      'weight' => ( $request->getParam('weight') ? true : false),
      'surname' => ( $request->getParam('surname') ? true : false),
      'shirtType' => ( $request->getParam('shirtType') ? true : false),
      'size' => ( $request->getParam('size') ? true : false),
      'teamPreference' => ( $request->getParam('teamPreference') ? true : false),
    ];
    
    // update data
    if($ticket->update($credentials)) {
      $this->flash->addMessage('success', "Ticket details for '{$credentials['sku']}' have been changed.");
    } else {
      $this->flash->addMessage('error', "Then ticket could not be updated.");
    }
    return $response->withRedirect($this->router->pathFor('admin.tickets.all'));
  }
  
  public function delete($request, $response, $arguments)
  {
    
  }
}