<?php

use Asundust\AuthCaptcha\Http\Controllers\AuthCaptchaController;

Route::get('auth/login', AuthCaptchaController::class . '@getLogin');
Route::post('auth/login', AuthCaptchaController::class . '@postLogin');
