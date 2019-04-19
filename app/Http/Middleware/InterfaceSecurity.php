<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Redis;
class InterfaceSecurity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */

    private $_api_data=[];
    private $_blank_key='blank_list';
    public function handle($request, Closure $next)
    {

        //先获取接口的数据，需要先解密
        //$encrypt_data=$this->_decrypt($request);
        $decrypt_data=$this->_rsaDecrypt($request);
        //接口防刷
        $data1=$this->_checkApiAccessCount();
        if($data1['status']!=1000){
            return response($data1);
        }
        //验证签名
        $data=$this->_checkClientSign($request);
        //把数据传给控制器
        $request->request->replace((array)$this->_api_data);
        //判断签名是否正确
        if($data['status']==1000){
            $response=$next($request);
            //后置加密，对返回的数据加密
            $response_arr=json_decode($response->original,true);
//            var_dump($response_arr['data']);die;
            $api_response=[];
            $api_response['data']=$this->_rsaEncrypt($response_arr['data']);
            $api_response['sign']=$this->_createServerSign($response_arr['data']);
            return response($api_response);

        }else{
            return response($data);
        }
    }
    //对返回的数据进行加密--对称加密
    private function _encrypt($data){
        //如果不是空的数据
        if(!empty($data)){
//            var_dump($data);die;
            $encrypt_param=openssl_encrypt(json_encode($data),'AES-256-CBC','guanshuqi',false,'1234567812345678');
//            var_dump($encrypt_param);
            return $encrypt_param;
        }
    }
    //对返回的数据进行加密--非对称加密
    private function _rsaEncrypt($data){
        //如果不是空的数据
        if(!empty($data)){
            $datas=json_encode($data);
            $i=0;
            $enrtypt_datas='';
            while($substr_data=substr($datas,$i,117)){
                openssl_private_encrypt($substr_data,$encrypt_data,file_get_contents('./key/private.key'),OPENSSL_PKCS1_PADDING);
                $enrtypt_datas.=base64_encode($encrypt_data);
                $i+=117;
            }
            return $enrtypt_datas;
        }
    }
    //服务端生成签名
    private function _createServerSign($data){
        ksort($data);
        $app_key=$this->_getAppIdKey();
        $server_str=md5(http_build_query($data).'&app_key='.$app_key[md5(2)]);
        return $server_str;
    }

    //解密接过来的数据--对称解密
    private function _decrypt($request){
        $data=$request->post('data');
//        var_dump($data);die;
        //如果不是空的数据
        if(!empty($data)){
            $decrypt_param=openssl_decrypt($data,'AES-256-CBC','guanshuqi',false,'1234567812345678');
//            var_dump($decrypt_param);die;
            $this->_api_data=json_decode($decrypt_param,true);
//            var_dump($this->_api_data);die;
        }
    }
    //解密接过来的数据--非对称解密
    private function _rsaDecrypt($request){
        $data=$request->post('data');
        $i=0;
        $all='';
        while($substr_data=substr($data,$i,172)){
            $data1=base64_decode($substr_data);
            openssl_private_decrypt($data1,$decrypt_data,file_get_contents('./key/private.key'),OPENSSL_PKCS1_PADDING);
//            var_dump($decrypt_data);
            $all.=$decrypt_data;
            $i+=172;
        }
        $this->_api_data=json_decode($all,true);
//        var_dump($this->_api_data);die;
    }

    //验证客户端签名
    private function _checkClientSign($request){
        if(!empty($this->_api_data)){
            //获取当前所有的appid和key
            $map=$this->_getAppIdKey();
//            var_dump($map);die;
            if(!array_key_exists($this->_api_data['app_id'],$map)){
                return [
                  'status'=>1,
                  'msg' =>'check sign fail1',
                  'data'=>[]
                ];
            }
            //生成服务端签名
            ksort($this->_api_data);

            //拼接appkey
            $server_str=http_build_query($this->_api_data).'&app_key='.$map[md5(2)];
//            echo $server_str;die;
//            var_dump($request['sign']);die;
            if(md5($server_str)!=$request['sign']){
                return [
                    'status'=>2,
                    'msg' =>'check sign fail2',
                    'data'=>[]
                ];
            }else{
                return [
                    'status'=>1000,
                    'msg' =>'check sign success',
                    'data'=>[]
                ];
            }
        }
    }
    //获取当前所有的appid和key
    private function _getAppIdKey(){
        //从数据库获取对应数据
        return [
          md5(2)=>md5('123123')
        ];
    }
    //获取当前appid
    private function _getAppId(){
        return $this->_api_data['app_id'];
    }
    //接口防刷
    private function _checkApiAccessCount(){
        $app_id=$this->_getAppId();
//        echo $app_id;die;
        $blank_key=$this->_blank_key;
        //判断是否在黑名单里
        $join_blank_time=Redis::zScore($blank_key,$app_id);
//        var_dump($join_blank_time);die;
        if(empty($join_blank_time)){
            $this->_addAppIdAccessCount();
            return ['status'=>1000];
        }else{
            //判断是否超过30分钟
            if(time()-$join_blank_time>=30){
                Redis::zRemove($blank_key,$app_id);
                $this->_addAppIdAccessCount();
            }else{
                return [
                    'status'=>3,
                    'msg' =>'many request!',
                    'data'=>[]
                ];
            }
        }
    }

    //记录访问次数
    private function _addAppIdAccessCount(){
        $count=Redis::incr($this->_getAppId());
        if($count==1){
            Redis::expire($this->_getAppId(),60);
        }
        //访问次数大于100，加入黑名单
        if($count>=100){
            Redis::zAdd($this->_blank_key,time(),$this->_getAppId());
            Redis::del($this->_getAppId());
            return [
                'status'=>3,
                'msg' =>'暂时不能访问，稍后再试!',
                'data'=>[]
            ];
        }
    }


}