@extends('auth-captcha::login_base')
@section('content')
    <div id="captchaError" class="form-group has-feedback {!! !$errors->has('captcha') ?: 'has-error' !!}"
         style="margin-bottom: 0;">
        @if($errors->has('captcha'))
            @foreach($errors->get('captcha') as $message)
                <label class="control-label" for="inputError"><i class="fa fa-times-circle-o"></i>{{ $message }}
                </label><br>
            @endforeach
        @endif
    </div>
    <div class="form-group row">
        <div class="col-xs-12" id="yunpianContainer"></div>
    </div>
    <div class="row">
        <div class="col-xs-8">
            @if(config('admin.auth.remember'))
                <div class="checkbox icheck">
                    <label>
                        <input type="checkbox" name="remember"
                               value="1" {{ (!old('username') || old('remember')) ? 'checked' : '' }}>
                        {{ trans('admin.remember_me') }}
                    </label>
                </div>
            @endif
        </div>
        <div class="col-xs-4">
            <input type="hidden" name="_token" value="{{ csrf_token() }}">
            <input type="hidden" id="token" name="token" value="">
            <input type="hidden" id="authenticate" name="authenticate" value="">
            <button type="button" class="btn btn-primary btn-block btn-flat" id="loginButton">
                {{ trans('admin.login') }}
            </button>
        </div>
    </div>
@endsection
@section('js')
    <script src="https://www.yunpian.com/static/official/js/libs/riddler-sdk-0.2.2.js"></script>
    <script>
        window.onload = function () {
            // 初始化
            new YpRiddler(Object.assign({
                mode: '{{ config('admin.extensions.auth-captcha.style') }}',
                winWidth: 320,
                container: $('#yunpianContainer'),
                appId: '{{ config('admin.extensions.auth-captcha.appid') }}',
                version: 'v1',
                onError: function (param) {
                    console.error(param);
                    failMessage('{{ __('Sliding validation failed. Please try again.') }}');
                },
                onSuccess: function (validInfo, close, useDefaultSuccess) {
                    $('#token').attr('value', validInfo.token);
                    $('#authenticate').attr('value', validInfo.authenticate);
                    useDefaultSuccess(true);
                    close();
                },
                onFail: function (code, msg, retry) {
                    failMessage('{{ __('Sliding validation failed. Please try again.') }}');
                    retry();
                }
            }, @json(config('admin.extensions.auth-captcha.ext_config', []))));
            $('#auth-login').on('keyup', function (event) {
                if (event.keyCode === 13) {
                    $('#loginButton').click();
                }
            });
            $('#loginButton').on('click', function (event) {
                formValidate();
            });
        };
    </script>
@endsection