<?php namespace App\Http\Controllers\Admin;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Category;


class AdminHomeController extends Controller {

	public function index()
	{
        return view('AdminHome')->with(['categories'=>Category::where('parent_id', '!=', 0)->get()]);
	}


}
