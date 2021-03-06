<?php
namespace App\Http\Controllers\Weixin;
use App\Model\WeixinUser;
use App\Model\WeixinMedia;
use App\Model\WeixinTalk;
use App\Model\UsersModel;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redis;
use GuzzleHttp;
use Illuminate\Support\Facades\Storage;
class IndexController extends Controller
{
    //
    protected $redis_weixin_access_token = 'str:weixin_access_token';     //微信 access_token
    protected $redis_weixin_jsapi_ticket = 'str:weixin_jsapi_ticket';     //微信 access_token
    /**
     * 刷新access_token
     */
    public function test()
    {
        Redis::del($this->redis_weixin_access_token);
        echo $this->getWXAccessToken();
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

        //记录日志
        $log_str = date('Y-m-d H:i:s') . "\n" . $data . "\n<<<<<<<";
        file_put_contents('logs/wx_event.log',$log_str,FILE_APPEND);

        $event = $xml->Event;                       //事件类型
        $openid = $xml->FromUserName;               //用户openid


        // 处理用户发送消息
        if(isset($xml->MsgType)){
            if($xml->MsgType=='text'){            //用户发送文本消息
                $msg = $xml->Content;
                $xml_response = '<xml><ToUserName><![CDATA['.$openid.']]></ToUserName><FromUserName><![CDATA['.$xml->ToUserName.']]></FromUserName><CreateTime>'.time().'</CreateTime><MsgType><![CDATA[text]]></MsgType><Content><![CDATA['. $msg. date('Y-m-d H:i:s') .']]></Content></xml>';
                echo $xml_response;

                //写入数据库
                $data = [
                    'openid'    => $openid,
                    'msg_type'  => 1,
                    'msg_content'    => $xml->Content,
                    'send_time'   => time()
                ];

                $m_id = WeixinTalk::insertGetId($data);
                var_dump($m_id);
            }elseif($xml->MsgType=='image'){       //用户发送图片信息
                //视业务需求是否需要下载保存图片
                if(1){  //下载图片素材
                    $file_name = $this->dlWxImg($xml->MediaId);
                    $xml_response = '<xml><ToUserName><![CDATA['.$openid.']]></ToUserName><FromUserName><![CDATA['.$xml->ToUserName.']]></FromUserName><CreateTime>'.time().'</CreateTime><MsgType><![CDATA[text]]></MsgType><Content><![CDATA['. date('Y-m-d H:i:s') .']]></Content></xml>';
                    echo $xml_response;

                    //写入数据库
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
                    var_dump($m_id);
                }

            }elseif($xml->MsgType=='voice'){        //处理语音信息
                $file_name=$this->dlVoice($xml->MediaId);
                $xml_response = '<xml><ToUserName><![CDATA['.$openid.']]></ToUserName><FromUserName><![CDATA['.$xml->ToUserName.']]></FromUserName><CreateTime>'.time().'</CreateTime><MsgType><![CDATA[text]]></MsgType><Content><![CDATA['. date('Y-m-d H:i:s') .']]></Content></xml>';
                echo $xml_response;
                //写入数据库
                $data = [
                    'openid'    => $openid,
                    'add_time'  => time(),
                    'msg_type'  => 'voice',
                    'media_id'  => $xml->MediaId,
                    'format'    => $xml->Format,
                    'msg_id'    => $xml->MsgId,
                    'local_file_name'   => $file_name
                ];

                $m_id = WeixinMedia::insertGetId($data);
                var_dump($m_id);
            }elseif($xml->MsgType=='event'){        //判断事件类型

                if($event=='subscribe'){                        //扫码关注事件
                    $sub_time = $xml->CreateTime;               //扫码关注时间
                    //获取用户信息
                    $user_info = $this->getUserInfo($openid);

                    //保存用户信息
                    $u = WeixinUser::where(['openid'=>$openid])->first();
                    if($u){       //用户不存在
                        //echo '用户已存在';
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
                        //var_dump($id);
                    }
                    $xml_response = '<xml><ToUserName><![CDATA['.$openid.']]></ToUserName><FromUserName><![CDATA['.$xml->ToUserName.']]></FromUserName><CreateTime>'.time().'</CreateTime><MsgType><![CDATA[text]]></MsgType><Content><![CDATA['.'您好,谢谢您的关注'.']]></Content></xml>';
                    echo $xml_response;
                }elseif($event=='CLICK'){               //click 菜单
                    if($xml->EventKey=='kefu'){       // 根据 EventKey判断菜单
                        $this->kefu($openid,$xml->ToUserName);
                    }
                }

            }elseif($xml->MsgType=='video'){
                $file_name=$this->dlVideo($xml->MediaId);
                $xml_response = '<xml><ToUserName><![CDATA['.$openid.']]></ToUserName><FromUserName><![CDATA['.$xml->ToUserName.']]></FromUserName><CreateTime>'.time().'</CreateTime><MsgType><![CDATA[text]]></MsgType><Content><![CDATA['. date('Y-m-d H:i:s') .']]></Content></xml>';
                echo $xml_response;
                //写入数据库
                $data = [
                    'openid'    => $openid,
                    'add_time'  => time(),
                    'msg_type'  => 'video',
                    'media_id'  => $xml->MediaId,
                    'format'    => $xml->Format,
                    'msg_id'    => $xml->MsgId,
                    'local_file_name'   => $file_name
                ];

                $m_id = WeixinMedia::insertGetId($data);
                var_dump($m_id);
            }

        }
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
     * @param $media_id
     */
    public function dlWxImg($media_id)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/media/get?access_token='.$this->getWXAccessToken().'&media_id='.$media_id;
        //echo $url;echo '</br>';

        //保存图片
        $client = new GuzzleHttp\Client();
        $response = $client->get($url);
        //$h = $response->getHeaders();
        //echo '<pre>';print_r($h);echo '</pre>';die;



        //获取文件名
        $file_info = $response->getHeader('Content-disposition');

        $file_name = substr(rtrim($file_info[0],'"'),-20);

        $wx_image_path = 'wx/images/'.$file_name;
        //保存图片
        $r = Storage::disk('local')->put($wx_image_path,$response->getBody());
        if($r){     //保存成功
            //echo 'OK';
        }else{      //保存失败
            //echo 'NO';
        }

        return $file_name;


    }

    /**
     * 下载语音文件
     * @param $media_id
     */
    public function dlVoice($media_id)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/media/get?access_token='.$this->getWXAccessToken().'&media_id='.$media_id;

        $client = new GuzzleHttp\Client();
        $response = $client->get($url);
        //$h = $response->getHeaders();
        //echo '<pre>';print_r($h);echo '</pre>';die;
        //获取文件名
        $file_info = $response->getHeader('Content-disposition');
        $file_name = substr(rtrim($file_info[0],'"'),-20);

        $wx_image_path = 'wx/voice/'.$file_name;
        //保存图片
        $r = Storage::disk('local')->put($wx_image_path,$response->getBody());
        if($r){     //保存成功

        }else{      //保存失败

        }
        return $file_name;
    }
    /**
     * 下载视频文件
     * @param $media_id
     */
    public function dlVideo($media_id)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/media/get?access_token='.$this->getWXAccessToken().'&media_id='.$media_id;

        $client = new GuzzleHttp\Client();
        $response = $client->get($url);
        //$h = $response->getHeaders();
        //echo '<pre>';print_r($h);echo '</pre>';die;

        //获取文件名
        $file_info = $response->getHeader('Content-disposition');

        $file_name = substr(rtrim($file_info[0],'"'),-20);


        $wx_image_path = 'wx/images/'.$file_name;
        //保存图片
        $r = Storage::disk('local')->put($wx_image_path,$response->getBody());
        if($r){     //保存成功
            //echo 'OK';
        }else{      //保存失败
            //echo 'NO';
        }

        return $file_name;


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
     * 上传素材
     */
    public function upMaterial()
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/material/add_material?access_token='.$this->getWXAccessToken().'&type=image';
        $client = new GuzzleHttp\Client();
        $response = $client->request('POST',$url,[
            'multipart' => [
                [
                    'name'     => 'username',
                    'contents' => 'zhangsan'
                ],
                [
                    'name'     => 'media',
                    'contents' => fopen('abc.jpg', 'r')
                ],
            ]
        ]);

        $body = $response->getBody();
        echo $body;echo '<hr>';
        $d = json_decode($body,true);
        echo '<pre>';print_r($d);echo '</pre>';


    }
    public function upMaterialTest($file_path)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/material/add_material?access_token='.$this->getWXAccessToken().'&type=image';
        $client = new GuzzleHttp\Client();
        $response = $client->request('POST',$url,[
            'multipart' => [
                [
                    'name'     => 'media',
                    'contents' => fopen($file_path, 'r')
                ],
            ]
        ]);

        $body = $response->getBody();
        echo $body;echo '<hr>';
        $d = json_decode($body,true);
        echo '<pre>';print_r($d);echo '</pre>';


    }
    /**
     * 获取永久素材列表
     */
    public function materialList()
    {
        $client = new GuzzleHttp\Client();
        $type = $_GET['type'];
        $offset = $_GET['offset'];

        $url = 'https://api.weixin.qq.com/cgi-bin/material/batchget_material?access_token='.$this->getWXAccessToken();

        $body = [
            "type"      => $type,
            "offset"    => $offset,
            "count"     => 20
        ];
        $response = $client->request('POST', $url, [
            'body' => json_encode($body)
        ]);

        $body = $response->getBody();
        echo $body;echo '<hr>';
        $arr = json_decode($response->getBody(),true);
        echo '<pre>';print_r($arr);echo '</pre>';


    }

    /**
     * 表单测试
     */
    public function formTest(){
        return view('weixin.weixin');

    }
    public function formShow(Request $request){
//        echo '<pre>';print_r($_POST);echo '</pre>';echo '<hr>';
//        echo '<pre>';print_r($_FILES);echo '</pre>';echo '<hr>';
        //保存文件
        $img_file=$request->file('media');
        //echo '<pre>';print_r($img_file);echo '</pre>';echo '<hr>';
        //原文件名
        $img_origin_name = $img_file->getClientOriginalName();
        echo 'originName: '.$img_origin_name;echo '</br>';
        //获取文件扩展名
        $file_ext = $img_file->getClientOriginalExtension();
        echo 'ext: '.$file_ext;echo '</br>';

        //重命名
        $new_file_name=str_random(10).'.'.$file_ext;
        echo 'ext: '.$new_file_name;echo '</br>';
        //保存文件
        $save_file_path=$request->media->storeAs('form_test',$new_file_name);//服务器保存路径
        echo 'save_file_path:'.$save_file_path;echo '<hr>';
        //上传至微信永久素材
        $this->upMaterialTest($save_file_path);
    }
    public function getChatMsg()
    {
        $openid = $_GET['openid'];  //用户openid
        $pos = $_GET['pos'];        //上次聊天位置
        $msg = WeixinTalk::where(['openid'=>$openid])->where('id','>',$pos)->first();
        if($msg){
            $response = [
                'errno' => 0,
                'data'  => $msg->toArray()
            ];

        }else{
            $response = [
                'errno' => 50001,
                'msg'   => '服务器异常，请联系管理员'
            ];
        }

        die( json_encode($response));

    }


    /**
     * 微信登录
     */
    public function login(){
        //echo __METHOD__;DIE;
        return view('weixin.login');
    }
    public function getCode(){
        // 1 回调拿到 code (用户确认登录后 微信会跳 redirect )
//        echo '<pre>';print_r($_GET);echo '</pre>';echo '<hr>';
//        echo '<pre>';print_r($_POST);echo '</pre>';

        $code = $_GET['code'];          // code

        //2 用code换取access_token 请求接口

        $token_url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=wxe24f70961302b5a5&secret=0f121743ff20a3a454e4a12aeecef4be&code='.$code.'&grant_type=authorization_code';
        $token_json = file_get_contents($token_url);
        $token_arr = json_decode($token_json,true);
//        echo '<hr>';
//        echo '<pre>';print_r($token_arr);echo '</pre>';

        $access_token = $token_arr['access_token'];
        $openid = $token_arr['openid'];

        // 3 携带token  获取用户信息
        $user_info_url = 'https://api.weixin.qq.com/sns/userinfo?access_token='.$access_token.'&openid='.$openid.'&lang=zh_CN';
        $user_json = file_get_contents($user_info_url);

        $user_arr = json_decode($user_json,true);
//        echo '<hr>';
        //echo '<pre>';print_r($user_arr);echo '</pre>';
        $unionid=$user_arr['unionid'];
        $name=$user_arr['nickname'];
        $data=WeixinUser::where(['unionid'=>$unionid])->first();
        if($data){
            echo '登陆成功';
        }else{
            $where=[
                'name'=>$name
            ];
            $users=UsersModel::insertGetId($where);
            if($users){
                $data=[
                    'uid'=>$users,
                    'openid'=>$user_arr['openid'],
                    'add_time'=>time(),
                    'nickname'=>$name,
                    'sex'=>$user_arr['sex'],
                    'headimgurl'=>$user_arr['headimgurl'],
                    'unionid'=>$unionid,
                    'subscribe_time'=>time(),
                ];
                $user=WeixinUser::insertGetId($data);
                if($user){
                    echo '存入数据库成功';
                }else{
                    echo '失败';
                }
            }else{
                echo '入库失败';
            }

        }
    }
    /**
     * 微信   jssdk
     */
    public function jssdk(){
        $jsconfig=[
            'appid'=>env('WEIXIN_APPID'),
            'timestamp'=>time(),
            'noncestr' =>str_random(10)

        ];
        $sign = $this->jssdkSign($jsconfig);
        $jsconfig['sign'] = $sign;
        $data=[
            'jsconfig'=>$jsconfig
        ];
        return view('weixin.jssdk',$data);
    }

    /**
     * 计算jssdk签名
     */
    public function jssdkSign($param)
    {
        $current_url = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];     //当前调用 jsapi的 url
        $ticket = $this->getJsapiTicket();
        $str =  'jsapi_ticket='.$ticket.'&noncestr='.$param['noncestr']. '&timestamp='. $param['timestamp']. '&url='.$current_url;
        $signature=sha1($str);
        return $signature;
    }
    public function getjsapiTicket(){
        //是否有缓存
        $ticket=Redis::get($this->redis_weixin_jsapi_ticket);
        if(!$ticket){
            //获取access_token   请求接口
            $access_token=$this->getWXAccessToken();
            $url='https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token='.$access_token.'&type=jsapi';
            $ticket_info=file_get_contents($url);
            $ticket_arr=json_decode($ticket_info,true);
            //print_r($ticket_arr);
            if(isset($ticket_arr['ticket'])){
                $ticket=$ticket_arr['ticket'];
                //缓存  过期时间
                Redis::set($this->redis_weixin_jsapi_ticket,$ticket);
                Redis::setTimeout($this->redis_weixin_jsapi_ticket,3600);
            }
        }
        return $ticket;
    }
}