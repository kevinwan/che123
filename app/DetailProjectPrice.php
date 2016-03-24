<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class DetailProjectPrice extends Model {

    protected $connection = 'pingjia';
    protected $table = 'autohome_maintain_prices';
	protected $fillable = ['detail_id', 'project_id', 'price'];

}
