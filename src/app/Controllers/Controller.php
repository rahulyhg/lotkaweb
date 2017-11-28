<?php

namespace App\Controllers;

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
}
