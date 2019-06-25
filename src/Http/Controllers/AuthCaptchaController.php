<?php

namespace AuthCaptcha\Http\Controllers;

use AuthCaptcha\AuthCaptcha;
use Encore\Admin\Controllers\AuthController as BaseAuthController;
use Illuminate\Foundation\Auth\ThrottlesLogins;
use Illuminate\Http\Request;

class AuthCaptchaController extends BaseAuthController
{
    use ThrottlesLogins;

    public $url = 'https://ssl.captcha.qq.com/ticket/verify';
    public $captchaAppid;
    public $captchaSecret;

    /**
     * AuthCaptchaController constructor.
     */
    public function __construct()
    {
        $this->captchaAppid = AuthCaptcha::config('appid');
        $this->captchaSecret = AuthCaptcha::config('secret');
    }

    public function getLogin()
    {
        if ($this->guard()->check()) {
            return redirect($this->redirectPath());
        }
        return view('auth-captcha::login');
    }

    public function postLogin(Request $request)
    {
        if (empty($request->input('ticket')) || empty($request->input('randstr'))) {
            return back()->withInput()->withErrors(['captcha' => __('Sliding validation failed. Please try again.')]);
        }

        $params = [
            'aid' => $this->captchaAppid,
            'AppSecretKey' => $this->captchaSecret,
            'Ticket' => $request->input('ticket'),
            'Randstr' => $request->input('randstr'),
            'UserIP' => $request->getClientIp()
        ];

        $content = $this->txCurl($this->url . '?' . http_build_query($params));
        $result = json_decode($content, true);

        if (!$result) {
            return back()->withInput()->withErrors(['captcha' => __('Sliding validation failed. Please try again.')]);
        }
        if ($result['response'] != 1) {
            return back()->withInput()->withErrors(['captcha' => __('Sliding validation failed. Please try again.')]);
        }

        $this->loginValidator($request->all())->validate();

        $credentials = $request->only([$this->username(), 'password']);
        $remember = $request->get('remember', false);

        if ($this->guard()->attempt($credentials, $remember)) {
            return $this->sendLoginResponse($request);
        }

        return back()->withInput()->withErrors([
            $this->username() => $this->getFailedLoginMessage(),
        ]);
    }

    /**
     * curl
     * @param $url
     * @return bool|string
     */
    public function txCurl($url)
    {
        $curl = curl_init();
        $timeout = 5;
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $timeout);
        $file_contents = curl_exec($curl);
        curl_close($curl);
        return $file_contents;
    }
}
