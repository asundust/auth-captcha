@extends('auth-captcha::login_base')
@section('content')
    <div id="captchaError"
         class="form-group has-feedback {!! !$errors->has('captcha') && ($extConfig['token'] ?? '') ?: 'has-error' !!}"
         style="margin-bottom: 0;">
        @if($errors->has('captcha'))
            @foreach($errors->get('captcha') as $message)
                <label class="control-label" for="inputError"><i class="fa fa-times-circle-o"></i>{{ $message }}
                </label><br>
            @endforeach
        @elseif (!($extConfig['token'] ?? ''))
            <label class="control-label" for="inputError"><i class="fa fa-times-circle-o"></i>{{ __('Config Error.') }}
            </label><br>
        @endif
    </div>
    <div class="form-group">
        <div v5-config="{name: 'token', host: '{{ config('admin.extensions.auth-captcha.host') }}', token: '{{ $extConfig['token'] ?? '' }}'}"></div>
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
            <input type="hidden" id="verify5_token" name="verify5_token" value="{{ $extConfig['token'] ?? '' }}">
            <button type="button" class="btn btn-primary btn-block btn-flat" id="loginButton">
                {{ trans('admin.login') }}
            </button>
        </div>
    </div>
@endsection
@section('js')
    <script src="https://s.verify5.com/assets/latest/v5.js" type="text/javascript"></script>
    <script>
        let v5 = new com.strato.Verify5({
            host: "{{ config('admin.extensions.auth-captcha.host') }}",
            token: "{{ $extConfig['token'] ?? '' }}"
        });

        $('#loginButton').on('click', function () {
            if ($('input[name=token]').attr('value')) {
                $('#auth-login').submit();
            } else {
                v5.verify(function (result) {
                    var success = result.success;
                    if (success) {
                        $('input[name=token]').attr('value', result.verifyId);
                        $('#auth-login').submit();
                    }
                });
            }
        });

        $('#auth-login').on('keyup', function (event) {
            if (event.keyCode === 13) {
                $('#loginButton').click();
            }
        });
    </script>
@endsection