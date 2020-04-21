@extends('auth-captcha::login_base')
@section('css')
    <style>
        .g-recaptcha div {
            width: 320px !important;
        }
    </style>
@endsection
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
    <div class="g-recaptcha" data-sitekey="{{ config('admin.extensions.auth-captcha.appid') }}"
         data-callback="recaptchaCallback" style="text-align: center;margin-bottom: 11px"></div>
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
    <script src="{{ rtrim(config('admin.extensions.auth-captcha.domain', 'https://recaptcha.net')) }}/recaptcha/api.js"
            async defer></script>
    <script>
        function recaptchaCallback(token) {
            $('#token').attr('value', token);
        }

        $('#loginButton').on('click', function () {
            formValidate();
        });

        $('#auth-login').on('keyup', function (event) {
            if (event.keyCode === 13) {
                formValidate();
            }
        });
    </script>
@endsection