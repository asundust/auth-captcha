@extends('auth-captcha::login_base')
@section('css')
    <style>
        #vaptchaContainer {
            width: 320px;
            height: {{ config('admin.extensions.auth-captcha.style', 'click') == 'click' ? '36px' : '184px' }};
        }

        .vaptcha-init-main {
            display: table;
            width: 100%;
            height: 100%;
            background-color: #EEEEEE;
        }

        .vaptcha-init-loading {
            display: table-cell;
            vertical-align: middle;
            text-align: center;
        }

        .vaptcha-init-loading > a {
            display: inline-block;
            width: 18px;
            height: 18px;
            border: none;
        }

        .vaptcha-init-loading > a img {
            vertical-align: middle
        }

        .vaptcha-init-loading .vaptcha-text {
            font-family: sans-serif;
            font-size: 12px;
            color: #CCCCCC;
            vertical-align: middle;
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
    <div class="form-group row">
        <div class="col-xs-4">
            <div id="vaptchaContainer">
                <div class="vaptcha-init-main">
                    <div class="vaptcha-init-loading">
                        <a href="https://www.vaptcha.com" target="_blank">
                            <img src="https://cdn.vaptcha.com/vaptcha-loading.gif"/>
                        </a>
                        <span class="vaptcha-text">VAPTCHA启动中...</span>
                    </div>
                </div>
            </div>
        </div>
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
                type: '{{ $captchaStyle }}',
                container: '#vaptchaContainer',
                offline_server: 'v.vaptcha.com'
            }, @json(config('admin.extensions.auth-captcha.ext_config', []))
        )).then(function (vaptchaObj) {
            vaptchaObj.render();
            vaptchaObj.listen('pass', function () {
                $('#token').attr('value', vaptchaObj.getToken());
            });
            $('#loginButton').on('click', function () {
                formValidate();
            });
            $('#reset').on('click', function () {
                vaptchaObj.reset();
            });
        });

        $('#auth-login').on('keyup', function (event) {
            if (event.keyCode === 13) {
                formValidate();
            }
        });
    </script>
@endsection