<?php

namespace App\Http\Controllers\Weixin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class IndexController extends Controller
{
    //首次接入
    public function valid(){
        echo $_GET['echostr'];
    }
    //微信接收推送事件
    public function valid1(){
        echo $_GET['echostr'];
        $data=file_get_contents("php://input");
        $log_str=date('Y-m-d H:i:s')."\n".$data."\n<<<<";
        file_put_contents('logs/wx_event.log',$log_str,FTLE_APPEND);
    }



}
