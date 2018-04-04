<?php

namespace App\Controllers\Admin\Participants;

use App\Models\Character;
use App\Models\Attribute;
use App\Models\Group;
use App\Models\User;
use App\Models\Relation;
use App\Models\Plot;
use App\Controllers\Controller;
use Respect\Validation\Validator as v;
use Slim\Views\Twig as View;
use App\Controllers\Admin\Participants\CharacterActionController as Char;

class PlotActionController extends Controller
{
  
  private function attributes() {
    return [
      'public', 'synopsis', 'reviewed', 'submitted_for_review'
    ];
  }
  
  private function options() {
    return [
      'characters' => Character::orderBy('name')->get(),
      'character_filters' => Char::characterAttributes(),
      'filter_comparison' => ['=','<','>','<>','<=','>='],
      'groups' => Group::orderBy('name')->get(),
      'plots' => Plot::orderBy('name')->get(),
      'set_attr' => self::attributes(),
    ];
  }
  
  private function handlePostData($request) {
    $credentials = [
      'name' => $request->getParam('name'),
      'description' => $request->getParam('description'),
    ];   
    
    $attributes = [ 
      'keys' => $request->getParam('attrKey'), 
      'values' => $request->getParam('attrVal')
    ];
    
    foreach (self::attributes() as $attr) {
      if ( strlen($request->getParam($attr)) ) {
        $attributes['keys'][] = $attr;
        $attributes['values'][] = $request->getParam($attr);
      }
    }
    
    $attribute_ids = [];
    
    foreach ($attributes['keys'] as $i => $attr_key) {
      $attribute_ids[] = Attribute::firstOrCreate([
        'name' => $attr_key, 
        'value' => $attributes['values'][$i]
      ])->id;
    }
    
    return [ 
      'values' => $credentials, 
      'attributes' => $attribute_ids,
      'characters' => self::paramToArray($request, 'character_ids'),
      'groups' => self::paramToArray($request, 'group_ids'),
      'plots' => self::paramToArray($request, 'plot_ids'),
    ];    
  }
  
  private function save($requestData, $request, $response, $arguments) {
    // update data
    $item = Plot::firstOrCreate(['id' => $arguments['uid']]);
    $item->update($requestData['values']);
    
    if($item->id) {
      $item->attr()->sync($requestData['attributes']);
      $item->characters()->sync($requestData['characters']);
      $item->groups()->sync($requestData['groups']);
      $item->plots()->sync($requestData['plots']);
  
      $this->flash->addMessage('success', "Details have been saved.");
    } else {
      $this->flash->addMessage('error', "The details could not be saved.");
    }
    
    if( $request->getParam('selfsave') == 1 ) {
      return $response->withRedirect($this->router->pathFor('admin.plot.edit', ['uid' => $arguments['uid']]) . "#saved");
    } else {
      return $response->withRedirect($this->router->pathFor('admin.plot.list'));
    }    
  }
  
  public function index($request, $response, $arguments)
  {
    $item = Plot::orderBy('name');
    
    return $this->view->render($response, "admin/participants/plots/list.html", [
      'list' => $item->get(),
    ]);
  }
  
  public function add($request, $response, $arguments)
  {
    $this->container->view->getEnvironment()->addGlobal('current', [
      'data' => [],
      'attr' => [],
      'new' => true
    ]);
    
    return $this->view->render($response, 'admin/participants/plots/edit.html', self::options());
  }
  
  public function post($request, $response, $arguments)
  {    
    return self::save(self::handlePostData($request), $request, $response, $arguments);
  }
  
  public function edit($request, $response, $arguments)
  {
    $item = Plot::where('id', $arguments['uid'])->first();
    $this->container->view->getEnvironment()->addGlobal('current', [
      'data' => $item,
      'attr' => self::mapAttributes( $item->attr ),
    ]);
    
    return $this->view->render($response, 'admin/participants/plots/edit.html', self::options());
  }
  
  public function delete($request, $response, $arguments)
  {
    $item = Plot::where('id', $arguments['uid'])->first();
    
    $item->attr()->sync([]);
    $item->characters()->sync([]);
    $item->groups()->sync([]);
    $item->plots()->sync([]);

    $item->delete();
    $this->flash->addMessage('warning', "{$item->name} was deleted.");
    return $response->withRedirect($this->router->pathFor('admin.plot.list'));
  }
  
  public function submitted_for_review($request, $response, $arguments)
  {
    //Filter by attribute
    $item = Plot::whereHas(
        'attr', function ($query) {
            $query->where([['name', 'submitted_for_review'], ['value', '<>', '0'], ['value', '<>', 'off']]);
        }
    )
    ->with('attr');
    
    return $this->view->render($response, "admin/participants/plots/list.html", [
      'list' => $item->get(),
    ]);
  }
  
  public function reviewed($request, $response, $arguments)
  {
    //Filter by attribute
    $item = Plot::whereHas(
        'attr', function ($query) {
            $query->where([['name', 'reviewed'], ['value', '<>', '0'], ['value', '<>', 'off']]);
        }
    )
    ->with('attr');
    
    return $this->view->render($response, "admin/participants/plots/list.html", [
      'list' => $item->get(),
    ]);
  }
  
  public function csv($request, $response, $arguments)
  {
    $plots = Plot::all();
    $list = [];
    foreach ($plots as $plot) {
      $list[] = [
        "data" => $plot,
        "attr" => self::mapAttributes( $plot->attr ),
        "characters" => $plot->characters, 
      ];
    }
    
    return $this->view->render($response, 'admin/participants/plots/csv.html', [
      'list' => $list
    ])->withHeader('Content-Type', 'text/csv');
  }  
}