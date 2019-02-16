<?php

namespace App\Http\Controllers\Weixin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class IndexController extends Controller
{
    //
    public function valid(){
        echo $_GET['echostr'];
    }
    public function valid1(){
        echo $_GET['echostr'];
    }
}
