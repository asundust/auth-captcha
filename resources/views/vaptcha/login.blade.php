@extends('auth-captcha::login_base')
@section('content')
    <div class="col-xs-4">
        <input type="hidden" name="_token" value="{{ csrf_token() }}">
        <input type="hidden" id="token" name="token" value="">
        <button type="button" id="vaptchaContainer"
                class="btn btn-primary btn-block btn-flat">{{ trans('admin.login') }}</button>
    </div>
@endsection
@section('js')
    <script src="https://cdn.vaptcha.com/v2.js"></script>
    <script>
        window.vaptcha(Object.assign({
                vid: '{{ config('admin.extensions.auth-captcha.appid') }}',
                type: 'invisible',
                lang: '{{ in_array(config('app.locale'), ['zh-CN', 'en', 'zh-TW']) ? config('app.locale') : 'en' }}',
            }, @json(config('admin.extensions.auth-captcha.ext_config', []))
        )).then(function (vaptchaObj) {
            vaptchaObj.listen('pass', function () {
                $('#token').attr('value', vaptchaObj.getToken());
                $('#auth-login').submit();
            });
            $('#vaptchaContainer').on('click', function () {
                vaptchaObj.validate();
            });
            $('#reset').on('click', function () {
                vaptchaObj.reset();
            });
        });

        $('#auth-login').bind('keyup', function (event) {
            if (event.keyCode === 13) {
                $('#vaptchaContainer').click();
            }
        });
    </script>
@endsection