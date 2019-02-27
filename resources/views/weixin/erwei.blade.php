@extends('user.bst')

@section('content')
    <input type="hidden" value="{{$code_url}}" id="code_url">
    <input type="hidden" value="{{$order_id}}" id="order_id">
    <div id="code1" align="center"></div>
@endsection
@section('footer')
    <script src="{{URL::asset('js/jquery-1.12.4.min.js')}}"></script>
    <script src="{{URL::asset('js/jquery.qrcode.min.js')}}"></script>
    <script>
        $(function(){
            var code_url=$('#code_url').val();
            var order_id=$('#order_id').val();
            console.log(order_id);
            console.log(code_url);
            $("#code1").qrcode({
                render: "canvas", //table方式
                width: 200, //宽度
                height:200, //高度
                text:code_url //任意内容
            });


        })
    </script>
@endsection