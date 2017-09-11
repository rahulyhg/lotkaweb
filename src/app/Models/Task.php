<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model as Model;

class Task extends Model
{
  protected $table = 'tasks';

  protected $fillable = [
    "title",
    "description",
    "due_date",
    "completed",
    "completed_at",
    "priority",
    "status",
    "user_id",    
  ]; 
  
  public function user()
  {
      return $this->belongsTo('App\Models\User');
  }  
}