<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model as Model;

class Order extends Model
{
  protected $table = 'orders';

  protected $fillable = [
    "email",
    "type",
    "amount",
    "name",
    "shirt_type",
    "size",
    "preference",
    "user_id",
    "attested_id",
    "orderdate",
    "origin",
  ]; 
  
  public function user()
  {
      return $this->hasOne('App\Models\User');
  }   
}