<?php

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

Route::get('/', function () {
    return view('welcome');
 //  phpinfo();
});

//微信
Route::get('valid','Wei\WeiController@valid');
Route::any('valid','Wei\WeiController@wxEvent');
Route::get('success_toke','Wei\WeiController@success_toke');
Route::get('test','Wei\WeiController@test');



Route::get('a','Wei\WeiController@a');


