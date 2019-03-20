<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\UsersModel;

class ApiController extends Controller
{
    //加密解密
    public function curl(){
        //echo '<pre>';print_r($_GET);echo '<pre>';DIE;
        $timestamp=$_GET['t'];
        //echo $timestamp;die;
        $key='hello';
        $salt='aaaaa';
        $method='AES-128-CBC';
        $iv=substr(md5($timestamp.$salt),5,16);
        $sign=base64_decode($_POST['sign']);
        $base64_data=$_POST['data'];
        //验签
        $pub_res=openssl_get_publickey(file_get_contents("./key/pub.key"));
        $res=openssl_verify($base64_data,$sign,$pub_res,OPENSSL_ALGO_SHA256);
        //var_dump($res);exit;

        if(!$res){
            //echo '验签失败';die;
        }
        //接受加密数据
        $post_data=base64_decode($base64_data);
        //print_r($post_data);die;
        $dec_str=openssl_decrypt($post_data,$method,$key,OPENSSL_RAW_DATA,$iv);
        //print_r($dec_str);
        if($dec_str){
            $now=time();
            $response=[
                'error'=>200,
                'msg'=>'ok',
                'data'=>'this is secret'
            ];
            $iv2=substr(md5($now.$salt),5,16);
            //加密响应数据
            $enc_data=openssl_encrypt(json_encode($response),$method,$key,OPENSSL_RAW_DATA,$iv2);
            $arr=[
                'time'=>$now,
                'data'=>base64_encode($enc_data)
            ];
            echo json_encode($arr);
        }
    }


    //接口测试
    public function test(){
        return 111;
    }

    public function login(Request $request){
        $name=$request->input('name');
        $pwd=$request->input('pwd');
        $userInfo=UsersModel::where(['name'=>$name,'password'=>$pwd])->first();
        if($userInfo){
            return json_encode(
                [
                    'status'=>200,
                    'msg'=>'登录成功'
                ]
            );
        }else{
            return json_encode(
                [
                    'status'=>500,
                    'message'=>'账号或密码有误'
                ]
            );
        }
    }
    //注册
    public function register(Request $request){
        $name=$request->input('name');
        $pwd=$request->input('pwd');
        $rpwd=$request->input('rpwd');
        $email=$request->input('email');

        //$userInfo=UsersModel::where(['password'=>$pwd,'rpassword'=>$rpwd])->first();
        if($pwd!=$rpwd){
            return '确认密码与密码不一致';
        }else{
            $userInfo=UsersModel::insert([
                'name' => $name,
                'password' => $pwd,
                'email' => $email
            ]);
            return '注册成功';
        }

    }
}