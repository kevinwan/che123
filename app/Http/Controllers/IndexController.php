<?php namespace App\Http\Controllers;

use App\App;
use App\Category;

class IndexController extends Controller {

    public function rankingList($cat_id)
    {
        $appList = App::dataHandle(App::where('category_id', '=', $cat_id)->orderBy('download', 'desc')->get());
        $category = Category::find($cat_id);
        if(!$appList || !$category) {
            header('Location:'.action('App\Http\Controllers\HomeController@index'));
        }

        return view('index.rankingList')->with(['appList'=>$appList, 'category'=>$category]);
    }

    public function appView($app_id)
    {
        $appInfo = App::dataHandle(App::find($app_id));
        if(!$appInfo) {
            header('Location:'.action('App\Http\Controllers\HomeController@index'));
        }
        $commendApps = App::dataHandle(App::where('category_id', '=', $appInfo->category_id)
            ->where('id', '!=', $app_id)
            ->orderBy('download', 'desc')->take(4)->get());

        return view('index.appView')->with(['appInfo'=>$appInfo, 'commendApps'=>$commendApps]);
    }

}