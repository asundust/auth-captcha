<?php

namespace Asundust\AuthCaptcha\Http\Controllers;

use Asundust\AuthCaptcha\AuthCaptcha;
use Asundust\AuthCaptcha\SDK\Dingxiang\CaptchaClient;
use Encore\Admin\Controllers\AuthController as BaseAuthController;
use Illuminate\Foundation\Auth\ThrottlesLogins;
use Illuminate\Http\Request;

class AuthCaptchaController extends BaseAuthController
{
    use ThrottlesLogins;

    public $captchaProvider;
    public $captchaAppid;
    public $captchaSecret;

    /**
     * AuthCaptchaController constructor.
     */
    public function __construct()
    {
        $this->captchaProvider = AuthCaptcha::config('provider');
        $this->captchaAppid = AuthCaptcha::config('appid');
        $this->captchaSecret = AuthCaptcha::config('secret');
    }

    public function getLogin()
    {
        if ($this->guard()->check()) {
            return redirect($this->redirectPath());
        }
        return view('auth-captcha::' . $this->captchaProvider . '.login');
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|mixed
     */
    public function postLogin(Request $request)
    {
        switch ($this->captchaProvider) {
            case 'tencent':
                return $this->captchaValidateTencent($request);
                break;
            case 'dingxiang':
                return $this->captchaValidateDingxiang($request);
                break;

            default:
                return back()->withInput()->withErrors(['captcha' => __('Error')]);
                break;
        }
    }

    /**
     * Tencent Captcha
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     */
    private function captchaValidateTencent(Request $request)
    {
        $ticket = $request->input('ticket', '');
        $randstr = $request->input('randstr', '');
        if (empty($ticket) || empty($randstr)) {
            return back()->withInput()->withErrors(['captcha' => __('Sliding validation failed. Please try again.')]);
        }

        $params = [
            'aid' => $this->captchaAppid,
            'AppSecretKey' => $this->captchaSecret,
            'Ticket' => $ticket,
            'Randstr' => $randstr,
            'UserIP' => $request->getClientIp()
        ];

        $url = 'https://ssl.captcha.qq.com/ticket/verify';
        $content = $this->txCurl($url . '?' . http_build_query($params));
        $result = json_decode($content, true);

        if (!$result) {
            return back()->withInput()->withErrors(['captcha' => __('Sliding validation failed. Please try again.')]);
        }
        if ($result['response'] != 1) {
            return back()->withInput()->withErrors(['captcha' => __('Sliding validation failed. Please try again.')]);
        }

        return $this->loginValidate($request);
    }

    /**
     * Dingxiang Captcha
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     */
    private function captchaValidateDingxiang(Request $request)
    {
        $token = $request->input('token', '');
        if (empty($token)) {
            return back()->withInput()->withErrors(['captcha' => __('Sliding validation failed. Please try again.')]);
        }

        $client = new CaptchaClient($this->captchaAppid, $this->captchaSecret);
        $client->setTimeOut(2);
        $response = $client->verifyToken($token);

        if ($response->serverStatus == 'SERVER_SUCCESS' && $response->result) {
            return $this->loginValidate($request);
        }
        return back()->withInput()->withErrors(['captcha' => __('Sliding validation failed. Please try again.')]);
    }

    /**
     * Login Validate
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     */
    private function loginValidate(Request $request)
    {
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
     *
     * @param $url
     * @return bool|string
     */
    private function txCurl($url)
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