<?php

namespace AuthCaptcha;

use Illuminate\Support\ServiceProvider;

class AuthCaptchaServiceProvider extends ServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function boot(AuthCaptcha $extension)
    {
        if (!AuthCaptcha::boot()) {
            return;
        }

        if ($views = $extension->views()) {
            $this->loadViewsFrom($views, 'auth-captcha');
        }

        if ($this->app->runningInConsole() && $assets = $extension->assets()) {
            $this->publishes(
                [$assets => public_path('vendor/asundust/auth-captcha')],
                'auth-captcha'
            );
        }

        $this->app->booted(function () {
            AuthCaptcha::routes(__DIR__ . '/../routes/web.php');
        });
    }
}
