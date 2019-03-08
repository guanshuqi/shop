@extends("user.bst")
@section('content')
    <h2 style="align-content: center;color: blue;">商品列表</h2>

    <table class="table table-striped">
        <thead>
        <tr class="active">
            <td>ID</td>
            <td>OPENID</td>
            <td>NICKNAME</td>
            <td>HEADIMGURL</td>
            <td>关注时间</td>
        </tr>
        </thead>
        <tbody>
        @foreach($userInfo as $v)
            <tr class="warning">
                <td>{{$v['id']}}</td>
                <td>{{$v['openid']}}</td>
                <td>{{$v['nickname']}}</td>
                <td><img src="{{$v['headimgurl']}}"></td>
                <td>{{date('Y-m-d H:i:s',$v['add_time'])}}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    {{$userInfo->links()}}
@endsection
