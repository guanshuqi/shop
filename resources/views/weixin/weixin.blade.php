@extends('user.bst')
@section('content')
    <form class="form-inline" action="/all" method="post">
        <div class="form-group">
            <label class="sr-only" for="goods_num">群发消息</label>
            <div class="input-group">
                <input type="text" class="form-control" name="aaa">
            </div>
        </div>
        <button type="submit" class="btn btn-primary" id="add_cart_btn">提交</button>
    </form>
@endsection