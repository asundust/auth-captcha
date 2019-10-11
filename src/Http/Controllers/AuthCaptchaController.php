<?php

namespace Asundust\AuthCaptcha\Http\Controllers;

use Asundust\AuthCaptcha\AuthCaptcha;
use Asundust\AuthCaptcha\SDK\Dingxiang\CaptchaClient;
use Encore\Admin\Controllers\AuthController as BaseAuthController;
use GuzzleHttp\Client;
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
            case 'vaptcha':
                return $this->captchaValidateVaptcha($request);
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
        $response = $this->newHttp()->get($url . '?' . http_build_query($params));
        $statusCode = $response->getStatusCode();
        $contents = $response->getBody()->getContents();

        if ($statusCode != 200) {
            return back()->withInput()->withErrors(['captcha' => __('Sliding validation failed. Please try again.')]);
        }
        $result = json_decode($contents, true);
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
     * Vaptcha Captcha
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     */
    private function captchaValidateVaptcha(Request $request)
    {
        $token = $request->input('token', '');
        if (empty($token)) {
            return back()->withInput()->withErrors(['captcha' => __('Sliding validation failed. Please try again.')]);
        }

        $params = [
            'id' => $this->captchaAppid,
            'secretkey' => $this->captchaSecret,
            'token' => $token,
            'ip' => $request->ip(),
        ];

        $url = 'http://api.vaptcha.com/v2/validate';
        $response = $this->newHttp()->post($url, [
            'form_params' => $params,
        ]);
        $statusCode = $response->getStatusCode();
        $contents = $response->getBody()->getContents();

        if ($statusCode != 200) {
            return back()->withInput()->withErrors(['captcha' => __('Sliding validation failed. Please try again.')]);
        }
        $result = json_decode($contents, true);
        if ($result['success'] != 1) {
            return back()->withInput()->withErrors(['captcha' => __('Sliding validation failed. Please try again.')]);
        }

        return $this->loginValidate($request);
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
     * Http
     *
     * @return Client
     */
    private function newHttp()
    {
        return new Client([
            'timeout' => 5,
            'verify' => false,
            'http_errors' => false,
        ]);
    }
}