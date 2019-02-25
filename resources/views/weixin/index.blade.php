<div class="container">


    <div class="chat" id="chat_div">

    </div>
    <hr>

    <form action="" class="form-inline">

        <input type="hidden" value="1" id="msg_pos">
        <textarea name="content" id="send_msg" cols="30" rows="3"></textarea>
        <button class="btn btn-info" id="send_msg_btn">Send</button>
    </form>
</div>
<script src="{{URL::asset('/js/weixin/wxtalk.js')}}"></script>