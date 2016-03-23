<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class Detail extends Model {

    protected $connection = 'pingjia';
    protected $table = 'autohome_maintain_details';
	protected $fillable = ['autohome_url', 'brand_id'];

}
