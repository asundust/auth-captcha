@extends('auth-captcha::login_base')
@section('content')
    <div id="dx"></div>
    <div class="col-xs-4">
        <input type="hidden" name="_token" value="{{ csrf_token() }}">
        <input type="hidden" id="token" name="token" value="">
        <button type="button" class="btn btn-primary btn-block btn-flat" id="DxCaptcha">
            {{ trans('admin.login') }}
        </button>
    </div>
@endsection
@section('js')
    <script src="https://cdn.dingxiang-inc.com/ctu-group/captcha-ui/index.js"></script>
    <script>
        let captcha = _dx.Captcha(document.getElementById('dx'),
            Object.assign({
                    appId: '{{ config('admin.extensions.auth-captcha.appid') }}',
                    style: 'popup',
                    language: '{{ config('app.locale') == 'zh-CN' ? 'cn' : 'en' }}',
                    success: function (token) {
                        $('#token').attr('value', token);
                        $('#auth-login').submit();
                    }
                }, @json(config('admin.extensions.auth-captcha.ext_config', []))
            ));

        document.getElementById('DxCaptcha').onclick = function () {
            captcha.show();
        };

        $('#auth-login').bind('keyup', function (event) {
            if (event.keyCode === 13) {
                $('#DxCaptcha').click();
            }
        });
    </script>
@endsection