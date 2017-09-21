<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model as Model;

class Plot extends Model
{
  protected $table = 'plots';
  
  protected $fillable = [
    "name",
    "description",
  ];
  
  public function attr()
  {
      return $this->belongsToMany('App\Models\Attribute', 'plot_attribute')->withTimeStamps(); 
  }

  public function characters()
  {
      return $this->belongsToMany('App\Models\Character', 'character_plots')->withTimeStamps();
  }
  
  public function groups()
  {
      return $this->belongsToMany('App\Models\Group', 'group_plot')->withTimeStamps();
  }
  
  public function plots()
  {
      return $this->belongsToMany('App\Models\Plot',  'plot_plot', 'plot_id', 'parent_plot_id')->withTimeStamps();
  }  
}
