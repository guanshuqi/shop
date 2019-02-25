<div class="container">


    <div class="chat" id="chat_div">

    </div>
    <hr>

    <form action="" class="form-inline">
        <input type="hidden" id="openid" value="{{$openid}}">
        <input type="hidden" value="1" id="msg_pos">
        <textarea name="content" id="send_msg" cols="30" rows="3"></textarea>
        <button class="btn btn-info" id="send_msg_btn">Send</button>
    </form>
</div>
<script>
    var openid = $("#openid").val();

    /*setInterval(function(){
        $.ajax({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            url     :   '/admin/touser/chat?openid=' + openid + '&pos=' + $("#msg_pos").val(),
            type    :   'get',
            dataType:   'json',
            success :   function(d){
                if(d.errno==0){     //服务器响应正常
                    //数据填充
                    var msg_str = '<h5>'+d.data.openid+':'+ d.data.msg_content+'</h5>';

                    $("#chat_div").append(msg_str);
                    $("#msg_pos").val(d.data.id)
                }else{

                }
            }
        });
    },5000);*/

    // 客服发送消息 begin
    $("#send_msg_btn").click(function(e){
        e.preventDefault();
        var send_msg = $("#send_msg").val().trim();
        var msg_str = '<p style="color: mediumorchid"> 客服：'+send_msg+'</p>';
        $("#chat_div").append(msg_str);
        $("#send_msg").val("");
        // 客服发送消息 end
        $.ajax({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            url     :   'touser',
            type    :   'post',
            data    :   {content:send_msg,openid:openid},
            //dataType:   'json',
            success :   function(d){
                console.log(d);
            /*if(d.errno==0){     //服务器响应正常
                //数据填充
                var msg_str = '<h5>'+d.data.openid+':'+ d.data.msg_content+'</h5>';

                $("#chat_div").append(msg_str);
                $("#msg_pos").val(d.data.id)
            }else{

            }*/
            }
        });
    });


</script>