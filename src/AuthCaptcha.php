<?php

namespace Asundust\AuthCaptcha;

use Encore\Admin\Extension;

class AuthCaptcha extends Extension
{
    public $name = 'auth-captcha';

    public $views = __DIR__.'/../resources/views';

    public $assets = __DIR__.'/../resources/assets';
}
