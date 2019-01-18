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
});

Route::get('/adduser','User\UserController@add');

//路由跳转
Route::redirect('/hello1','/world1',301);
Route::get('/world1','Test\TestController@world1');

Route::get('hello2','Test\TestController@hello2');
Route::get('world2','Test\TestController@world2');


//路由参数
Route::get('/user/{uid}','User\UserController@user');
Route::get('/month/{m}/date/{d}','Test\TestController@md');
Route::get('/name/{str?}','Test\TestController@showName');



// View视图路由
Route::view('/mvc','mvc');
Route::view('/error','error',['code'=>403]);


// Query Builder
Route::get('/query/get','Test\TestController@query1');
Route::get('/query/where','Test\TestController@query2');

Route::match(['get','post'],'/test','Test\TestController@test');
Route::get('/date','Test\TestController@date');
Route::get('/userList','User\UserController@userList');
Route::get('/dump','User\UserController@dump');

//注册
Route::any('/register','Login\LoginController@registerAdd');
//登录
Route::any('/login','Login\LoginController@login');
Route::any('/bootstorp','Test\TestController@bootstorp');
Route::any('/login/center','Login\LoginController@center');
Route::any('/quit','Login\LoginController@quit');
//中间件
Route::any('/test/check_cookie','Test\TestController@checkCookie')->middleware('check.cookie');
//购物车
Route::any('/cart','Cart\CartController@index')->middleware('check.login.token');
Route::get('/cartAdd/{goods_id}','Cart\CartController@cartAdd')->middleware('check.login.token');
Route::post('/add2','Cart\CartController@add2');
Route::get('/cartDel/{goods_id}','Cart\CartController@cartDel')->middleware('check.login.token');
Route::post('/cartDel2','Cart\CartController@cartdel2')->middleware('check.login.token');
//商品列表
Route::get('/goodsList','Login\LoginController@goodsList');
//商品详情
Route::get('/goods/{goods_id}','Goods\GoodsController@index');
//订单
Route::get('/orderAdd','Order\OrderController@orderAdd');
Route::get('/orderList','Order\OrderController@orderList');
Route::get('/test0','Pay\AliPayController@test0');
Route::get('/pay','Pay\AliPayController@test');
//支付宝调回

Route::get('/orderPay/{oid}','Pay\AliPayController@pay')->middleware('check.login.token');
Route::post('/pay/alipay/notify','Pay\AliPayController@aliNotify');//异步通知
Route::get('/pay/alipay/return','Pay\AliPayController@aliReturn');//同步通知


Route::get('/pay/alipay/orderDel','Pay\AliPayController@orderDel');//同步通知

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');
