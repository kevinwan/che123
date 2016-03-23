<?php

Route::get('auth/login', 'Auth\AuthController@getLogin');
Route::post('auth/login', 'Auth\AuthController@postLogin');
Route::get('auth/logout', 'Auth\AuthController@getLogout');


Route::get('/rankingList/{cat_id}', 'IndexController@rankingList');
Route::get('/appView/{app_id}', 'IndexController@appView');

Route::group(['domain' => 'admin.'.env('DOMAIN'), 'namespace' => 'Admin', 'middleware' => 'auth'], function()
{
    Route::get('/', 'AdminHomeController@index');
    Route::get('/app/all-apps', 'AppController@getAllApps');
    Route::resource('/app', 'AppController');
});

Route::group(['domain' => 'api.'.env('DOMAIN'), 'namespace' => 'Api'], function()
{
    Route::get('/valuation', 'ValuationController@index');
    Route::get('/recommendCar', 'RecommendCarController@index');
    
    Route::get('/maintain/addRecord', 'MaintainController@addRecord');
    Route::get('/maintain/makeAppointment', 'MaintainController@makeAppointment');
    Route::get('/maintain/getAllTypes', 'MaintainController@getAllTypes');
    Route::get('/maintain/showModelData', 'MaintainController@showModelData');
    Route::get('/maintain', 'MaintainController@index');

});



Route::get('/', 'HomeController@index');
