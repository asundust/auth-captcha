<?php

use Asundust\AuthCaptcha\Http\Controllers\AuthCaptchaController;

Route::get('auth/login', config('admin.extensions.auth-captcha.controller', AuthCaptchaController::class).'@getLogin');
Route::post('auth/login', config('admin.extensions.auth-captcha.controller', AuthCaptchaController::class).'@postLogin');
