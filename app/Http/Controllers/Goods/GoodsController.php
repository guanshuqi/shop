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


    //文件上传视图
    public function uploadIndex(){
        return view('goods.upload');
    }
    //文件上传
    public function uploadDF(Request $request){
        $pdf=$request->file('upload');
        $ext=$pdf->extension();
        if($ext !='pdf'){
            die('请上传PDF格式的文件');
        }
        $res=$pdf->storeAs(date('Ymd'),str_random(5).'.pdf');
        if($res){
            echo '上传成功';
        }
    }
    //搜索
    public function search(Request $request){
        $search = $request->input('s');
        if (empty($search)) {
            $newslist =GoodsModel::get();
        } else {
            $newslist =GoodsModel::where([
                ['goods_name', 'like', "%$search%"]
            ])->get();
        }

        return view('goods.goodsList', $newslist);
    }
}
