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
    <script src="https://v.vaptcha.com/v3.js"></script>
    <script>
        vaptcha(Object.assign({
                vid: '{{ $captchaAppid }}',
                type: 'invisible',
                offline_server: 'v.vaptcha.com'
            }, @json(config('admin.extensions.auth-captcha.ext_config', []))
        )).then(function (vaptchaObj) {
            vaptchaObj.listen('pass', function () {
                $('#token').attr('value', vaptchaObj.getToken());
                $('#auth-login').submit();
            });
            $('#loginButton').on('click', function () {
                vaptchaObj.validate();
            });
            $('#reset').on('click', function () {
                vaptchaObj.reset();
            });
        });

        $('#auth-login').on('keyup', function (event) {
            if (event.keyCode === 13) {
                $('#loginButton').click();
            }
        });
    </script>
@endsection