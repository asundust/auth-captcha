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
            <button type="button" class="btn btn-primary btn-block btn-flat h-captcha" id="loginButton"
                    data-sitekey="{{ config('admin.extensions.auth-captcha.appid') }}"
                    data-callback="hCaptchaCallback">
                {{ trans('admin.login') }}
            </button>
        </div>
    </div>
@endsection
@section('js')
    <script src="https://hcaptcha.com/1/api.js" async defer></script>
    <script>
        function hCaptchaCallback(token) {
            $('#token').attr('value', token);
            $('#auth-login').submit();
        }

        $('#loginButton').on('click', function () {
            hcaptcha.execute();
        });

        $('#auth-login').on('keyup', function (event) {
            if (event.keyCode === 13) {
                hcaptcha.execute();
            }
        });
    </script>
@endsection