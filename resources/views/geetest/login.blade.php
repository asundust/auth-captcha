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
            <button type="button" class="btn btn-primary btn-block btn-flat g-recaptcha" id="loginButton">
                {{ trans('admin.login') }}
            </button>
        </div>
    </div>
@endsection
@section('js')
    <script src="{{ admin_asset('vendor/laravel-admin-ext/auth-captcha/geetest/gt.js') }}"></script>
    <script>
        initGeetest(Object.assign({
                width: '320px',
                gt: '{{ $extConfig['gt'] }}',
                challenge: '{{ $extConfig['challenge'] }}',
                new_captcha: {{ $extConfig['new_captcha'] }},
                product: 'bind',
                offline: !{{ $extConfig['success'] }}
            }, @json(config('admin.extensions.auth-captcha.ext_config', []))
        ), function (captchaObj) {
            captchaObj.onReady(function () {
                $('#loginButton').on('click', function () {
                    captchaObj.verify();
                });
                $('#auth-login').on('keyup', function (event) {
                    if (event.keyCode === 13) {
                        captchaObj.verify();
                    }
                });
            }).onSuccess(function () {
                captchaObj.bindForm('#auth-login');
                $('#auth-login').submit();
            }).onError(function () {
                //
            })
        });
    </script>
@endsection