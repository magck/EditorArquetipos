<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class fileObject extends Eloquent
{
    protected $connection = 'mongodb';
    protected $collection = 'Datos';
    
    protected $fillable = ['nombre', 'extension','archivo']; 
}
