<form action="/weixin/login11" method="post">
    <h2>注册</h2>
    {{csrf_field()}}
    <table  class="table table-bordered">
        <tr>
            <td>账号</td>

            <td><input type="text" name="name"></td>
        </tr>
        <tr>
            <td>密码</td>
            <td><input type="password" name="pwd"></td>
        </tr>
        <tr>
            <td>确认密码</td>
            <td><input type="password" name="pwd"></td>
        </tr>
    </table>
    <button class="btn btn-primary">注册</button>
</form>
