<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/



//Shop routes
Route::get('/', 'ShopController@index');



//Cart routes
Route::get('/cart', 'CartController@index');
Route::get('/cart/add/{id}', 'CartController@add');
Route::get('/cart/remove/{id}', 'CartController@remove');

Route::post('/cart/update', 'CartController@update');

Route::get('/cart/pay', 'CartController@payment');
Route::post('/cart/pay/submit', 'CartController@paymentSubmit');



