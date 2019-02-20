<?php
namespace App\Http\Controllers\Weixin;
use App\Model\WeixinUser;
use App\Model\weixinMedia;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redis;
use GuzzleHttp;
use Illuminate\Support\Facades\Storage;
class IndexController extends Controller
{
    //
    protected $redis_weixin_access_token = 'str:weixin_access_token';     //微信 access_token
    public function test()
    {
        //echo __METHOD__;
        echo 'token:'.$this->getWXAccessToken();
    }
    /**
     * 首次接入
     */
    public function validToken1()
    {
        //$get = json_encode($_GET);
        //$str = '>>>>>' . date('Y-m-d H:i:s') .' '. $get . "<<<<<\n";
        //file_put_contents('logs/weixin.log',$str,FILE_APPEND);
        echo $_GET['echostr'];
    }
    /**
     * 接收微信服务器事件推送
     */
    public function wxEvent()
    {
        $data = file_get_contents("php://input");
        //解析XML
        $xml = simplexml_load_string($data);        //将 xml字符串 转换成对象
        $event = $xml->Event;                       //事件类型
        $openid = $xml->FromUserName;
        //var_dump($xml);echo '<hr>';
        //处理用户发送信息
        if(isset($xml->MsgType)){
            if($xml->MsgType=='text'){
                $msg=$xml->Content;
                $xml_response = '<xml><ToUserName><![CDATA['.$openid.']]></ToUserName><FromUserName><![CDATA['.$xml->ToUserName.']]></FromUserName><CreateTime>'.time().'</CreateTime><MsgType><![CDATA[text]]></MsgType><Content><![CDATA['. $msg. date('Y-m-d H:i:s') .']]></Content></xml>';
                echo $xml_response;
            }else if($xml->MsgType=='image'){
                //视业务需求是否需要下载保存图片
                //下载图片素材
                if(1){
                    $file_name=$this->dlWxImg($xml->MediaId);
                    $data = [
                        'openid'    => $openid,
                        'add_time'  => time(),
                        'msg_type'  => 'image',
                        'media_id'  => $xml->MediaId,
                        'format'    => $xml->Format,
                        'msg_id'    => $xml->MsgId,
                        'local_file_name'   => $file_name
                    ];

                    $m_id = WeixinMedia::insertGetId($data);
                    var_dump($m_id); die;
                    $xml_response = '<xml><ToUserName><![CDATA['.$openid.']]></ToUserName><FromUserName><![CDATA['.$xml->ToUserName.']]></FromUserName><CreateTime>'.time().'</CreateTime><MsgType><![CDATA[text]]></MsgType><Content><![CDATA['. str_random(10) . ' >>> ' . date('Y-m-d H:i:s') .']]></Content></xml>';
                    echo $xml_response;
                    //写入数据库

                }
            }
        }
        //判断事件类型
        if($event=='subscribe'){
            //用户openid
            $sub_time = $xml->CreateTime;               //扫码关注时间
            echo 'openid: '.$openid;echo '</br>';
            echo '$sub_time: ' . $sub_time;
            //获取用户信息
            $user_info = $this->getUserInfo($openid);
            echo '<pre>';print_r($user_info);echo '</pre>';
            //保存用户信息
            $u = WeixinUser::where(['openid'=>$openid])->first();
            //var_dump($u);die;
            if($u){       //用户不存在
                echo '用户已存在';
            }else{
                $user_data = [
                    'openid'            => $openid,
                    'add_time'          => time(),
                    'nickname'          => $user_info['nickname'],
                    'sex'               => $user_info['sex'],
                    'headimgurl'        => $user_info['headimgurl'],
                    'subscribe_time'    => $sub_time,
                ];
                $id = WeixinUser::insertGetId($user_data);      //保存用户信息
                var_dump($id);
            }
        }else if($event=='CLICK'){
            if($xml->EventKey=='kefu'){
                $this->kefu($openid,$xml->ToUserName);
            }
        }
        $log_str = date('Y-m-d H:i:s') . "\n" . $data . "\n<<<<<<<";
        file_put_contents('logs/wx_event.log',$log_str,FILE_APPEND);
    }
    /**
     * 客服处理
     */
    public function kefu($openid,$from)
    {
        // 文本消息
        $xml_response = '<xml><ToUserName><![CDATA['.$openid.']]></ToUserName><FromUserName><![CDATA['.$from.']]></FromUserName><CreateTime>'.time().'</CreateTime><MsgType><![CDATA[text]]></MsgType><Content><![CDATA['. 'Hello World, 现在时间'. date('Y-m-d H:i:s') .']]></Content></xml>';
        echo $xml_response;
    }
    /**
     * 下载图片素材
     */
    public function dlWxImg($media_id){
        $url='https://api.weixin.qq.com/cgi-bin/media/get?access_token='.$this->getWXAccessToken().'&media_id='.$media_id;
        //保存图片
        $client = new GuzzleHttp\Client();
        $response = $client->get($url);
        //获取文件名
        $file_info = $response->getHeader('Content-disposition');
        $file_name = substr(rtrim($file_info[0],'"'),-20);
        $wx_image_path = 'weixin/images/'.$file_name;
        //保存图片
        $r = Storage::disk('local')->put($wx_image_path,$response->getBody());
        if($r){     //保存成功
            echo '保存成功';
        }else{      //保存失败
            echo '保存失败';
        }
    }
    /*
     * 接收事件推送
     */
    public function validToken()
    {
        //$get = json_encode($_GET);
        //$str = '>>>>>' . date('Y-m-d H:i:s') .' '. $get . "<<<<<\n";
        //file_put_contents('logs/weixin.log',$str,FILE_APPEND);
        //echo $_GET['echostr'];
        $data = file_get_contents("php://input");
        $log_str = date('Y-m-d H:i:s') . "\n" . $data . "\n<<<<<<<";
        file_put_contents('logs/wx_event.log',$log_str,FILE_APPEND);
    }
    /**
     * 获取微信AccessToken
     */
    public function getWXAccessToken()
    {
        //获取缓存
        $token = Redis::get($this->redis_weixin_access_token);
        if(!$token){        // 无缓存 请求微信接口
            $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.env('WEIXIN_APPID').'&secret='.env('WEIXIN_APPSECRET');
            $data = json_decode(file_get_contents($url),true);
            //记录缓存
            $token = $data['access_token'];
            Redis::set($this->redis_weixin_access_token,$token);
            Redis::setTimeout($this->redis_weixin_access_token,3600);
        }
        return $token;
    }
    /**
     * 获取用户信息
     * @param $openid
     */
    public function getUserInfo($openid)
    {
        //$openid = 'oLreB1jAnJFzV_8AGWUZlfuaoQto';
        $access_token = $this->getWXAccessToken();
        $url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$access_token.'&openid='.$openid.'&lang=zh_CN';
        $data = json_decode(file_get_contents($url),true);
        //echo '<pre>';print_r($data);echo '</pre>';
        return $data;
    }
    /*
     *创建服务器菜单
     */
    public function createMenu(){
        //1 获取access_token   拼接请求接口
        $access_token=$this->getWXAccessToken();
        $url='https://api.weixin.qq.com/cgi-bin/menu/create?access_token='.$access_token;
        //echo $url;
        //2 请求微信接口
        $client = new GuzzleHttp\Client(['base_uri' => $url]);
        $data = [
            "button"    => [
                [
                    "type"  => "view",      // view类型 跳转指定 URL
                    "name"  => "guanshuqi",
                    "url"   => "https://www.baidu.com"
                ],
                [
                    "name"=>"发送图片",
                    "sub_button"=>[
                        [
                            "type"=>"pic_photo_or_album",
                            "name"=>"拍照或者相册发图",
                            "key"=>"rselfmenu_1_1",
                            "sub_button"=>[ ]
                        ],
                        [
                            "type"=>"pic_weixin",
                            "name"=>"微信相册发图",
                            "key"=>"rselfmenu_1_2",
                            "sub_button"=>[ ]
                        ],
                    ],
                ],
                [
                    "type"  => "click",      // click类型
                    "name"  => "客服",
                    "key"   => "kefu"
                ]
            ]
        ];
        $body = json_encode($data,JSON_UNESCAPED_UNICODE);//处理中文编码
        $r = $client->request('POST', $url, [
            'body' => $body
        ]);
        //3 解析微信返回信息
        $response_arr=json_decode($r->getBody(),true);
        echo '<pre>';print_r($response_arr);echo '</pre>';
        if($response_arr['errcode']==0){
            echo '菜单创建成功';
        }else{
            echo '菜单创建失败'.'</pre>';
            echo $response_arr['errmsg'];
        }
    }

    /**
     * 群发消息
     */
    public function all()
    {
        $access_token = $this->getWXAccessToken();
        $url = 'https://api.weixin.qq.com/cgi-bin/message/mass/sendall?access_token='.$access_token;
        //var_dump($url);exit;
        $client = new GuzzleHttp\Client(['base_url' => $url]);
        $param = [
            "filter"=>[
                "is_to_all"=>true
            ],
            "text"=>[
                "content"=>"苗博学是傻子."
            ],
            "msgtype"=>"text"
        ];
        ///var_dump($param);exit;
        $r = $client->Request('POST', $url, [
            'body' => json_encode($param, JSON_UNESCAPED_UNICODE)
        ]);
        //var_dump($r);exit;
        $response_arr = json_decode($r->getBody(), true);
        //echo '<pre>';
        //print_r($response_arr);
        // echo '</pre>';

        if ($response_arr['errcode'] == 0) {
            echo "发送成功";
        } else {
            echo "发送失败";
            echo '</br>';
            echo $response_arr['errmsg'];

        }

    }
}