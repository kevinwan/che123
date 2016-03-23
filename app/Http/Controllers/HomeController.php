<?php namespace App\Http\Controllers;

use App\Category;

class HomeController extends Controller {

    public function index()
    {
        $Category = new Category();
        return view('home')->withCategories($Category->formedCategories());
    }


}