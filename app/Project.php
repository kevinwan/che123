<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class Project extends Model {

    protected $connection = 'pingjia';
    protected $table = 'autohome_maintain_projects';
    protected $fillable = ['name'];

}
