<?php

namespace App\Http\Controllers\Goods;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\GoodsModel;

class GoodsController extends Controller
{
    //商品详情
    public function index($goods_id){
        $goods=GoodsModel::where(['goods_id'=>$goods_id])->first();
        //该商品是否存在
        if(!$goods){
            header('Refresh:2;url=/login/center');
            echo "该商品不存在,正在跳转到商品列表页面";
            exit;
        }
        $data=[
            'goods'=>$goods
        ];
        return view('goods.goods',$data);
    }
}