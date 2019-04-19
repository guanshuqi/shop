<?php
namespace App\Http\Controllers\Test;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redis;
class IndexController extends Controller{
    public function openssl(Request $request){
//        var_dump($request->all());die;
        $data=[
            'status'=>1000,
            'msg' =>'check sign success',
            'data'=>$request->all()
        ];
       return json_encode($data);
    }
    public function uploadImg(Request $request){
//        var_dump($request->post('contents'));die;
        $data=base64_decode($request->post('contents'));
        if(empty($data)){
            return ['status'=>5,'data'=>[],'msg'=>'上传的文件内容不能为空'];
        }
        //指定文件存储路径
        $file_save_path=app_path().'/storage/uploads/'.date('Ym').'/';
//        var_dump($file_save_path);die;
        if(!is_dir($file_save_path)){
            mkdir($file_save_path,0777,true);
        }
        $file_name=time().rand(1000,9999).'.tmp';
        $byte=file_put_contents($file_save_path.$file_name,$data);
//        var_dump($byte);die;
        if($byte>0){
            //查看文件格式
            $info=getimagesize($file_save_path.$file_name);
//            var_dump($info);die;
            if(!$info) {
                return ['status' => 6, 'data' => [], 'msg' => '上传的文件格式不正确'];
            }
            //判断文件格式
            switch($info['mime']){
                case 'image/jpeg':
                    $new_file_name=str_replace('tmp','jpeg',$file_name);
                    break;
                case 'image/jpg':
                    $new_file_name=str_replace('tmp','jpg',$file_name);
                    break;
                default:
                    return ['status' => 6, 'data' => [], 'msg' => '上传的文件格式不正确'];
                    break;
            }
            //文件重命名
            rename($file_save_path.$file_name,$file_save_path.$new_file_name);
            $api_response=[];
            $access_path=str_replace(app_path().'/storage','',$file_save_path);
//            var_dump($access_path);die;
            $api_response['access_path']=env('FILE_PATH').$access_path.$new_file_name;
//            var_dump($api_response);die;
            return json_encode(['status' => 1000, 'data' => [], 'msg' => $api_response]);
        }
    }


    //获取验证码url
    public function getVcodeUrl(){
        session_start();
        $sid=session_id();
//        var_dump($sid);die;
        $url='http://shop.com/showVcode/'.$sid;
        $api_response= [
              'url'=>$url,
              'sid'=>$sid
            ];
        return json_encode(['status'=>1000,'msg'=>'success','data'=>$api_response]);
    }
    //生成验证码
    public function showVcode(Request $request,$sid){
        session_id($sid);
        session_start();
//        $rand=(string) rand(1000,9999);
        $a=rand(1,9);
        $b=rand(1,9);
        $c=$a*$b;
        $_SESSION['code']=$a;
//        $len=strlen($c);
        header('content-type:image/png');
        // Create the image 创建画布
        $im = imagecreatetruecolor(120, 30);

        // Create some colors  创建几个颜色
        $white = imagecolorallocate($im, 255, 255, 255);
        $grep = imagecolorallocate($im, 128, 128, 128);
        $black = imagecolorallocate($im, 0, 0, 0);
        //填充画布的背景色
        imagefilledrectangle($im, 0, 0, 399, 299, $white);
        // Replace path by your own font path  字体文件
        $font = '/www/shop/a.ttf';
        $text=$c.'/'.$b.'=?';
        $len=strlen($text);
//        var_dump($len);die;
        $i=0;
        while($i<$len) {
            if(is_numeric($text[$i])){
                imagettftext($im, 15, rand(-30, 30),11+20 * $i, 20, $black, $font, $text[$i]);
            }else{
                imagettftext($im, 15, rand(0,0), 11+20 * $i, 20, $black, $font, $text[$i]);
            }
            $i++;
        }

        imagepng($im);
        imagedestroy($im);
        exit();
    }
    //验证验证码
    public function checkurl(Request $request){
        $sid =$request->post('sid');
        $vcode= $request->post('vcode');
        $key='str:user:vcode';
        Redis::set($key,$vcode);
        session_id($sid);
        session_start();
        $code=$_SESSION['code'];
        if($code==$vcode){
           return json_encode([
                'status'=>1000,
                'msg'   =>'success',
                'data'  =>[]
            ]);
        }else{
            return json_encode([
                'status'=>5000,
                'msg'   =>'验证码错误',
                'data'  =>[]
            ]);
        }
    }
}