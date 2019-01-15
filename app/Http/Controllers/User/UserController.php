<?php

namespace App\Http\Controllers\User;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Model\UserModel;

class UserController extends Controller
{
    //

	public function user($uid)
	{
		echo $uid;
	}

	public function add()
	{
		$data = [
			'name'      => str_random(5),
			'age'       => mt_rand(20,99),
			'email'     => str_random(6) . '@gmail.com',
			'reg_time'  => time()
		];

		$id = UserModel::insertGetId($data);
		var_dump($id);
	}
	public function userList(){
	    $info=userModel::all()->toArray();
	    $data=[
	        'title'=>'lening',
	        'info'=>$info,
        ];
	    return view('test.test',$data);
    }
    public function dump(){
        header('Location:http://www.baidu.com');
    }
}
