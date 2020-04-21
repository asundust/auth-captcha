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
            <input type="hidden" id="ticket" name="ticket" value="">
            <input type="hidden" id="randstr" name="randstr" value="">
            <button type="button"
                    class="btn btn-primary btn-block btn-flat"
                    id="TencentCaptcha"
                    data-appid="{{ $captchaAppid }}"
                    data-cbfn="callback"
            >{{ trans('admin.login') }}</button>
        </div>
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

        $('#auth-login').on('keyup', function (event) {
            if (event.keyCode === 13) {
                $('#TencentCaptcha').click();
            }
        });
    </script>
@endsection