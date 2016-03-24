<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class DetailSection extends Model {

    protected $connection = 'pingjia';
    protected $table = 'autohome_maintain_sections';
    protected $fillable = ['section', 'detail_id', 'project_ids'];

}
