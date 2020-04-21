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
            <button type="button" class="btn btn-primary btn-block btn-flat" id="loginButton">
                {{ trans('admin.login') }}
            </button>
        </div>
    </div>
@endsection
@section('js')
    <script src="{{ rtrim(config('admin.extensions.auth-captcha.domain', 'https://recaptcha.net')) }}/recaptcha/api.js?render={{ config('admin.extensions.auth-captcha.appid') }}"></script>
    <script>
        grecaptcha.ready(function () {
            $('#loginButton').on('click', function (event) {
                grecaptcha.execute('{{ config('admin.extensions.auth-captcha.appid') }}', {action: 'login'}).then(function (token) {
                    $('#token').attr('value', token);
                    $('#auth-login').submit();
                });
            });
        });

        $('#auth-login').on('keyup', function (event) {
            if (event.keyCode === 13) {
                $('#loginButton').click();
            }
        });
    </script>
@endsection