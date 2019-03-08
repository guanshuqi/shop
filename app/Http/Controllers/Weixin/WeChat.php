<?php

namespace App\Http\Controllers\Weixin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\WeixinUser;
use GuzzleHttp;
use Illuminate\Support\Facades\Redis;

class WeChat extends Controller
{
    protected $wx_user_info_redis='str:wx_user_info';

    /**
     * 获取access_token
     */
    public function getAccessToken(){
        $url='https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.env('WEIXIN_APPID').'&secret='.env('WEIXIN_APPSECRET');
        //echo $url;
        $json=file_get_contents($url);
        $data=json_decode($json,true);
        $access_token=$data['access_token'];
        return $access_token;
    }
    /**
     * 获取用户列表
     */
    public function getUserList(){
        $userInfo=WeixinUser::first()->toArray();
        //print_r($userInfo);die;
        $openid=$userInfo['openid'];
        $access_token = $this->getAccessToken();
        $url='https://api.weixin.qq.com/cgi-bin/user/get?access_token='.$access_token.'&next_openid='.$openid.'';
        $json=file_get_contents($url);
        $data=json_decode($json,true);
        print_r($data);
        //echo $url;
    }
    /**
     * 关注人数据存redis
     */
    public function userInfoRedis(){
        $userInfo=WeixinUser::paginate(2);
        $data=[
            'userInfo'=>$userInfo
        ];
        return view('weixin.userInfo',$data);
        //print_r($userInfo);die;
        $userInfo=json_encode($userInfo);
        Redis::hMSet($this->wx_user_info_redis,$userInfo);
    }

    /**
     * 黑名单
     */
    public function getBlack(){
        $access_token=$this->getAccessToken();
        $url='https://api.weixin.qq.com/cgi-bin/tags/members/batchblacklist?access_token='.$access_token.'';
        $json=file_get_contents($url);
        $data=json_decode($json,true);
        print_r($data);
    }
    /**
     * 创建标签
     */
    public function createSign(){
        $access_token=$this->getAccessToken();
        $url='https://api.weixin.qq.com/cgi-bin/tags/create?access_token='.$access_token.'';
        //请求微信接口
        $client = new GuzzleHttp\Client(['base_uri' => $url]);
        $tag=[
            'tag'=>[
                'name'=>'www'
            ]
        ];
        $body = json_encode($tag,JSON_UNESCAPED_UNICODE);//处理中文编码
        $r = $client->request('POST', $url, [
            'body' => $body
        ]);
        //3 解析微信返回信息
        $response_arr=json_decode($r->getBody(),true);
        //print_r($response_arr);die;
        echo '<pre>';print_r($response_arr);echo '</pre>';
        if($response_arr['errcode']==0){
            echo '标签创建成功';
        }else{
            echo '标签创建失败'.'</pre>';
            echo $response_arr['errmsg'];
        }
    }
}
