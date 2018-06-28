<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::get('/', function () {
    return view('page.welcome');
});

Route::get('install', function () {
    return view('page.install');
});
Route::post('install', 'AppController@shopifyAuth');

Route::get('connect', 'AppController@connect');

Route::get('app', 'AppController@app');

Route::get('setup', 'AppController@setup');
Route::get('upwork', 'AppController@upwork');
Route::get('upworkcallback', 'AppController@upworkCallback');

//Route::get('artnaturals', 'AppController@art');
//Route::get('artorders', 'AppController@artOrders');
//Route::get('artimages', 'AppController@artImages');
//Route::get('artroutes', 'AppController@artRoutes');

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::post('api/order', 'ApiController@order');
Route::get('api/order', 'ApiController@order');