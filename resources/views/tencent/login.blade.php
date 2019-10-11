@extends('auth-captcha::login_base')
@section('content')
    <div class="col-xs-4">
        <input type="hidden" name="_token" value="{{ csrf_token() }}">
        <input type="hidden" id="ticket" name="ticket" value="">
        <input type="hidden" id="randstr" name="randstr" value="">
        <button type="button"
                class="btn btn-primary btn-block btn-flat"
                id="TencentCaptcha"
                data-appid="{{ config('admin.extensions.auth-captcha.appid') }}"
                data-cbfn="callback"
        >{{ trans('admin.login') }}</button>
    </div>
@endsection
@section('js')
    <script src="https://ssl.captcha.qq.com/TCaptcha.js"></script>
    <script>
        window.callback = function (res) {
            if (res.ret === 0) {
                $('#ticket').attr('value', res.ticket);
                $('#randstr').attr('value', res.randstr);
                $('#auth-login').submit();
            }
        };

        $('#auth-login').bind('keyup', function (event) {
            if (event.keyCode === 13) {
                $('#TencentCaptcha').click();
            }
        });
    </script>
@endsection