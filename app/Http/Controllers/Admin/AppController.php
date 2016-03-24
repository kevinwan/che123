<?php namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\App;
use App\Category;
use App\FileHandler;

use Redirect, Input, Auth;

class AppController extends Controller {


    public function store(Request $request)
    {
        $app = new App();
        $app->name = Input::get('name');
        $app->category_id = Category::where('name', '=', Input::get('class_name'))->pluck('id');
        $app->slogan = Input::get('slogan');
        $app->introduce = Input::get('introduce');
        $app->android = Input::get('android');
        $app->ios = Input::get('ios');
        $app->download = Input::get('download');
        $app->is_check = 0;

        //图片处理
        $tmp_name = $_FILES['logo']['tmp_name'];
        if($tmp_name) {
            $result_pic = $data['logo'] = FileHandler::uploadPic($tmp_name);
            if($result_pic['success'])  $app->logo = $result_pic['name'];
            else  return Redirect::back()->withInput()->withErrors($result_pic['msg']);
        }

        if ($app->save()) {
            return Redirect::back();
        } else {
            return Redirect::back()->withInput()->withErrors('保存失败！');
        }

    }

    public function update(Request $request,$id)
    {
        $app = App::find($id);
        $app->name = Input::get('name');
        $app->category_id = Category::where('name', '=', Input::get('class_name'))->pluck('id');
        $app->slogan = Input::get('slogan');
        $app->introduce = Input::get('introduce');
        $app->android = Input::get('android');
        $app->ios = Input::get('ios');
        $app->download = Input::get('download');
        $app->is_check = 1;

        //图片处理
        $tmp_name = $_FILES['logo']['tmp_name'];
        if($tmp_name) {
            $result_pic = $data['logo'] = FileHandler::uploadPic($tmp_name);
            if($result_pic['success'])  $app->logo = $result_pic['name'];
            else  return Redirect::back()->withInput()->withErrors($result_pic['msg']);
        }

        if ($app->save()) {
            return Redirect::back();
        } else {
            return Redirect::back()->withInput()->withErrors('保存失败！');
        }
    }

    public function destroy($id)
    {
        $app = App::find($id);
        $app->delete();

        return Redirect::back();
    }

    public function getAllApps()
    {
        $apps = App::join('categories', 'apps.category_id', '=', 'categories.id')
            ->select('apps.id', 'apps.name', 'apps.category_id', 'apps.logo', 'apps.slogan', 'apps.introduce', 'apps.android'
                , 'apps.ios', 'apps.download', 'apps.is_check', 'apps.created_at', 'apps.updated_at', 'categories.name as class_name')
            ->orderBy('apps.updated_at', 'desc')->get();
        foreach($apps as $app){
            $array = array();
            $array[] = '<div style="white-space:nowrap;">
                <button type="button" class="btn btn-success" data-toggle="modal" data-target="#saveModal" data-remark="edit">编辑</button>
                <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#delModal">删除</button>
                <input type="hidden" name="id" value="'.$app->id.'">
                </div>';
            $src = $app->logo ? config('app.pic_path').$app->logo : '';
            $array[] = '<img style="height:50px;width:50px" class="logo" src="'.$src.'">';
            $array[] = '<span style="display:block;overflow:hidden;white-space:nowrap;">'.$app->name.'</span>';
            $array[] = $app->class_name;
            $array[] = $app->download;
            $array[] = $app->is_check ? '<span style="background-color:green">已审核</span>' : '<span style="background-color:red">未审核</span>';
            $array[] = $app->android;
            $array[] = $app->ios;
            $array[] = '<span style="display:block;overflow:hidden;white-space:nowrap;">'.$app->slogan.'</span>';
            $array[] = '<span style="display:block;overflow:hidden;white-space:nowrap;">'.$app->introduce.'</span>';


            $result[] = $array;
        }
        echo json_encode(array("success"=>true, "data"=>$result));
    }

}
