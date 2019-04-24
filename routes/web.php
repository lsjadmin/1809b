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
//微信首次接入
Route::get('valid','Wei\WeiController@valid');
Route::any('valid','Wei\WeiController@wxEvent');
Route::get('success_toke','Wei\WeiController@success_toke');
Route::get('test','Wei\WeiController@test');
//微信创建菜单
Route::any('createMenu','Wei\WeiController@createMenu');
//测试
Route::get('a','Wei\WeiController@a');
//群发 
Route::get('send','Wei\WeiController@send');
//微信支付
Route::get('text','Wei\WeiPayController@text');
Route::post('notify','Wei\WeiPayController@notify');
//回复图文消息
Route::any('desc/{id}','Wei\WeiController@desc');




