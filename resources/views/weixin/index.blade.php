@extends('layouts.bst')

@section('content')
<form action="" method="post" enctype="multipart/form-data">
    {{csrf_field()}}
    <div>
        <h2>{{$openid}}</h2>
        <ul>
            <li>

            </li>
        </ul>
    </div>
    <input type="text" name="aaa">
    <input type="submit" value="发送">
</form>
@endsection
