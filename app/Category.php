<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class Category extends Model {

	public function apps()
    {
        $apps = $this->hasMany('App\App');
        return App::dataHandle($apps);
    }

    /*
     * 结构化分类
     */
    public function formedCategories() {
        $categories = self::where('parent_id', '=', 0)->get();
        foreach($categories as $cat)
        {


            $cat->_child = self::where('parent_id', '=', $cat->id)->get();
        }
        return $categories;
    }

}
