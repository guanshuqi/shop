<?php

namespace App\Http\Controllers\Pay;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\OrderModel;
class AliPayController extends Controller
{
    //
    public $app_id;
    public $gate_way;
    public $notify_url;
    public $return_url;
    public $rsaPrivateKeyFilePath = './key/priv.key';
    public $aliPubKey = './key/ali_pub.key';
    public function __construct()
    {
        $this->app_id = env('ALIPAY_APPID');
        $this->gate_way = env('ALIPAY_GATEWAY');
        $this->notify_url = env('ALIPAY_NOTIFY_URL');
        $this->return_url=env('ALIPAY_RETURN_URL');
    }

    /**
     * 请求订单服务 处理订单逻辑
     *
     */
    public function test0()
    {
        //
        $url = 'http://order.lening.com';
        // $client = new Client();
        $client = new Client([
            'base_uri' => $url,
            'timeout'  => 2.0,
        ]);

        $response = $client->request('GET', '/order.php');
        echo $response->getBody();


    }


    public function pay($order_id)
    {
        //验证订单状态
        $orderInfo=OrderModel::where(['order_id'=>$order_id])->first()->toArray();
        //是否已支付
        if($orderInfo['is_pay']==1){
            die('订单已支付，请勿重复支付');
        }
        //是否已删除
        if($orderInfo['is_delete']==1){
            die('订单已支付，请勿重复支付');
        }

        $bizcont = [
            'subject'           => 'Lening_shop'. $order_id,
            'out_trade_no'      => $orderInfo['order_sn'],
            'total_amount'      => $orderInfo['add_amount']/100,
            'product_code'      => 'QUICK_WAP_WAY',

        ];

        $data = [
            'app_id'   => $this->app_id,
            'method'   => 'alipay.trade.wap.pay',
            'format'   => 'JSON',
            'charset'   => 'utf-8',
            'sign_type'   => 'RSA2',
            'timestamp'   => date('Y-m-d H:i:s'),
            'version'   => '1.0',
            'notify_url'   => $this->notify_url,//异步通知地址
            'return_url'   => $this->return_url,//同步通知地址
            'biz_content'   => json_encode($bizcont),
        ];
        //签名
        $sign = $this->rsaSign($data);
        $data['sign'] = $sign;
        $param_str = '?';
        foreach($data as $k=>$v){
            $param_str .= $k.'='.urlencode($v) . '&';
        }
        $url = rtrim($param_str,'&');
        $url = $this->gate_way . $url;
        header("Location:".$url);
    }


    public function rsaSign($params) {
        return $this->sign($this->getSignContent($params));
    }

    protected function sign($data) {

        $priKey = file_get_contents($this->rsaPrivateKeyFilePath);
        $res = openssl_get_privatekey($priKey);

        ($res) or die('您使用的私钥格式错误，请检查RSA私钥配置');

        openssl_sign($data, $sign, $res, OPENSSL_ALGO_SHA256);

        if(!$this->checkEmpty($this->rsaPrivateKeyFilePath)){
            openssl_free_key($res);
        }
        $sign = base64_encode($sign);
        return $sign;
    }


    public function getSignContent($params) {
        ksort($params);
        $stringToBeSigned = "";
        $i = 0;
        foreach ($params as $k => $v) {
            if (false === $this->checkEmpty($v) && "@" != substr($v, 0, 1)) {

                // 转换成目标字符集
                $v = $this->characet($v, 'UTF-8');
                if ($i == 0) {
                    $stringToBeSigned .= "$k" . "=" . "$v";
                } else {
                    $stringToBeSigned .= "&" . "$k" . "=" . "$v";
                }
                $i++;
            }
        }

        unset ($k, $v);
        return $stringToBeSigned;
    }

    protected function checkEmpty($value) {
        if (!isset($value))
            return true;
        if ($value === null)
            return true;
        if (trim($value) === "")
            return true;

        return false;
    }


    /**
     * 转换字符集编码
     * @param $data
     * @param $targetCharset
     * @return string
     */
    function characet($data, $targetCharset) {

        if (!empty($data)) {
            $fileType = 'UTF-8';
            if (strcasecmp($fileType, $targetCharset) != 0) {
                $data = mb_convert_encoding($data, $targetCharset, $fileType);
            }
        }


        return $data;
    }
    /**
     * 支付宝同步通知回调
     */
    public function aliReturn()
    {
        header('Refresh:2;url=/orderList');
        echo '订单：'.$_GET['out_trade_no']. '支付成功，正在跳转';
        //验签 支付宝的公钥
//        if(!$this->verify()){
//            echo 'error';
//        }
//
//        //处理订单逻辑
//        $this->dealOrder($_GET);
    }

    /**
     * 支付宝异步通知
     */
    public function aliNotify()
    {

        //$data = json_encode($_POST);
        //$log_str = '>>>> '.date('Y-m-d H:i:s') . $data . "<<<<\n\n";
        //记录日志
        //file_put_contents('logs/alipay.log',$log_str,FILE_APPEND);
        $json='{"gmt_create":"2019-01-15 23:09:16","charset":"utf-8","seller_email":"grykdp4969@sandbox.com","subject":"Lening_shop2","sign":"YLxqz8nN8TbR9vPeIvWV9P5sbeeppVrIo3cG64S2gCjBnE1MGWU5DD+9pwfnPINnbG0ZfASquevjAsh+l6kQFjQf2nbclR+0UCRrLzwJKU1fGCvroEiVt0hHv14cspM6OJPU6jKmBs5zaL3YmaBdKA9\/ZgFJofrhoKSLEd6T1MmPmEpGjF\/0ZWx8ZBHZ6gfSBboRmnXWqr3QZxV9\/DvrFmLpHMS3T\/nR3KIUUSYm\/q\/DyDJ8tWCJVoM0O9rT0Dc2SxEa5oAXuovVqgFTi57npIL+de6jtgt1mPDLm0J\/+N3kx9ch2eAIhK6e2NtHjAywUpd7aE\/Fnd01e2X2voRtlg==","buyer_id":"2088102177375427","invoice_amount":"5.00","notify_id":"0711769d82edbbfba8c128a06251ca5j8t","fund_bill_list":"[{\"amount\":\"5.00\",\"fundChannel\":\"ALIPAYACCOUNT\"}]","notify_type":"trade_status_sync","trade_status":"TRADE_SUCCESS","receipt_amount":"5.00","app_id":"2016092200571824","buyer_pay_amount":"5.00","sign_type":"RSA2","seller_id":"2088102176797697","gmt_payment":"2019-01-15 23:09:16","notify_time":"2019-01-15 23:09:17","version":"1.0","out_trade_no":"1901151107794673115","total_amount":"5.00","trade_no":"2019011522001475420500569869","auth_app_id":"2016092200571824","buyer_logon_id":"jhe***@sandbox.com","point_amount":"0.00"}';
        $_POST=json_decode($json,true);
        //验签
        $res = $this->verify($_POST);

        $log_str = '>>>> ' . date('Y-m-d H:i:s');
        if($res === false){
            //记录日志 验签失败
            $log_str .= " Sign Failed!<<<<< \n\n";
            file_put_contents('logs/alipay.log',$log_str,FILE_APPEND);
        }else{
            $log_str .= " Sign OK!<<<<< \n\n";
            file_put_contents('logs/alipay.log',$log_str,FILE_APPEND);
        }
        //验证订单状态
        if($_POST['trade_status']=='TRADE_SUCCESS'){
            //更新订单状态
            $oid = $_POST['out_trade_no'];     //商户订单号
            $info = [
                'is_pay'        => 1,       //支付状态  0未支付 1已支付
                'pay_amount'    => $_POST['total_amount'] * 100,    //支付金额
                'pay_time'      => strtotime($_POST['gmt_payment']), //支付时间
                'plat_oid'      => $_POST['trade_no'],      //支付宝订单号
                'plat'          => 1,      //平台编号 1支付宝 2微信
            ];

            OrderModel::where(['order_id'=>$oid])->update($info);
        }

        //处理订单逻辑
        $this->dealOrder($_POST);

        echo 'success';
    }


    //验签
    function verify($params) {
        $sign = $params['sign'];
        $params['sign_type'] = null;
        $params['sign'] = null;

        //读取公钥文件
        $pubKey = file_get_contents($this->aliPubKey);
        $pubKey = "-----BEGIN PUBLIC KEY-----\n" .
            wordwrap($pubKey, 64, "\n", true) .
            "\n-----END PUBLIC KEY-----";
        //转换为openssl格式密钥

        $res = openssl_get_publickey($pubKey);
        ($res) or die('支付宝RSA公钥错误。请检查公钥文件格式是否正确');

        //调用openssl内置方法验签，返回bool值

        $result = (openssl_verify($this->getSignContent($params), base64_decode($sign), $res, OPENSSL_ALGO_SHA256)===1);
        openssl_free_key($res);

        return $result;
    }
    /**
     * 处理订单逻辑 更新订单 支付状态 更新订单支付金额 支付时间
     * @param $data
     */
    public function dealOrder($data)
    {


        //加积分

        //减库存
    }
}
