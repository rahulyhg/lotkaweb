<?php

namespace App\Controllers;

use App\Models\Post;
use App\Models\User;
use App\Models\Order;
use App\Models\Attribute;

class Controller
{
  protected $container;

  public function __construct($container)
  {
    $this->container = $container;
  }

  public function __get($property)
  {
    if ($this->container->{$property}) {
      return $this->container->{$property};
    }
  }
  
  public function paramToArray($request, $key) {
    $param = $request->getParam($key);
    return is_array($param) ? $param : [$param];
  }
  
  public function mapAttributes($collection) {
    $a = [];
    foreach ($collection as $name => $value) {
      if(isset($a[$value->name])) {
        if(!is_array($a[$value->name])) $a[$value->name] = [$a[$value->name]];
        $a[$value->name][] = $value->value;
      } else {     
        $a[$value->name] = $value->value; 
      }
    }        
    return $a;
  }
  
  # Helpers
  public function getAttributeIds($attributes = [ 'keys' => [], 'values' => [] ]) {
    $attribute_ids = [];
    foreach ($attributes['keys'] as $i => $attr_key) {
      $attribute_ids[] = Attribute::firstOrCreate([
        'name' => $attr_key, 
        'value' => $attributes['values'][$i]
      ])->id;
    }
    return $attribute_ids;
  }
  
  public function setModelAttributes($request, $model_attribute_list, $model, $extra_attributes = []) {
    $model_attributes = self::mapAttributes($model->attr);
    #Supply extra attributes not present in the origninal request to be able to set system attributes.
    $request_attributes = array_merge( $request->getParsedBody(), $extra_attributes );    
    
    # We prepopulate boolean attributes so they can be deactivated, otherwise they are not even sent
    if( isset($request_attributes["boolean_attributes"]) ) {
      $boolean_attributes = explode(',', $request->getParam("boolean_attributes"));
      foreach ($boolean_attributes as $a) {
        $request_attributes[$a] = isset($request_attributes[$a]) ? $request_attributes[$a] : 'false';
      }
    }
    
    $attributes = [ 'keys' => [], 'values' => [] ];

    foreach ($model_attribute_list as $attr) {
      $attribute_value = null;
      
      if(array_key_exists($attr, $model_attributes)) $attribute_value = $model_attributes[$attr]; //Prepolulate existing attr
      if(isset($request_attributes[$attr])) $attribute_value = $request_attributes[$attr]; //Update if we have new values

      if ( is_array($attribute_value) ? count($attribute_value) : strlen($attribute_value) ) {
        if(is_array($attribute_value)) {
          foreach ($attribute_value as $i => $val) {
            $attributes['keys'][] = $attr;
            $attributes['values'][] = $attribute_value[$i];
          }
        } else {
          $attributes['keys'][] = $attr;
          $attributes['values'][] = $attribute_value;
        }
      }
    }

    $updated_attribute_ids = self::getAttributeIds( $attributes );
        
    return $model->attr()->sync($updated_attribute_ids);
  }  
  
  public function render($slug, $info, $response) {
    $participant = self::getCurrentUser();
    $post = Post::where('slug', $slug)
      ->visibleTo(['participant', 'admin'])
      ->published()->first();
    
    if(!$post) die("Template '$slug' is missing, have a nice day.");
    
    $this->container->view->getEnvironment()->addGlobal(
      $info['key'], $info['data']
    );
    
    return $this->view->render($response, '/new/participant/page.html', [
      'post' => $post,
      'current' => $participant
    ]);
  }
  
  public function getPlayerInfo($uid) {
    $user_data = User::where('id', $uid)->first();

    return $user_data? [
      "user" => $user_data,
      "attributes" => self::mapAttributes( $user_data->attr ),
      "order" => Order::where('user_id', $user_data->id)->first()
    ] : [];
  }
  
  public function getCurrentUser() {
    $participant = User::where(
      'username',
      $this->container->sentinel->getUser()->username
    )->first();
    
    return $participant ? [
      "user" => $participant,
      "attributes" => self::mapAttributes( $participant->attr ),
      "order" => Order::where('user_id', $participant->id)->first()
    ] : [];
  }    
}
