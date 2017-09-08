<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model as Model;

class Surname extends Model
{
  protected $table = 'surnames';

  protected $fillable = [
    'surname', 
    'order_id', 
    'available', 
  ]; 
}