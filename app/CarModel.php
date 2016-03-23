<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class CarModel extends Model {

    protected $connection = 'pingjia';
    protected $table = 'autohome_models';
    public  $timestamps = false;

}
