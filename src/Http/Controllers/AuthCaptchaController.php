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
    public $captchaStyle;

    private $providerStyles = [
        'dingxiang' => [
            'popup' => 'login',
            'embed' => 'login_style',
            'inline' => 'login_style',
            'oneclick' => 'login_style',
        ],
        'tencent' => [
            'popup' => 'login',
        ],
        'verify5' => [
            'default' => 'login_style',
        ],
        'vaptcha' => [
            'invisible' => 'login',
            'click' => 'login_style',
            'embed' => 'login_style',
        ],
        'wangyi' => [
            'popup' => 'login',
            'float' => 'login_style',
            'embed' => 'login_style',
            'bind' => 'login',
            '' => 'login_style',
        ],
    ];

    /**
     * AuthCaptchaController constructor.
     */
    public function __construct()
    {
        $this->captchaProvider = AuthCaptcha::config('provider');
        $this->captchaAppid = AuthCaptcha::config('appid');
        $this->captchaSecret = AuthCaptcha::config('secret');
        $this->captchaStyle = AuthCaptcha::config('style');
    }

    /**
     * Get Login
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|\Illuminate\Support\Facades\Redirect|\Illuminate\View\View
     */
    public function getLogin()
    {
        if ($this->guard()->check()) {
            return redirect($this->redirectPath());
        }

        $extConfig = [];
        switch ($this->captchaProvider) {
            case 'dingxiang':
            case 'tencent':
                if (!$this->captchaStyle) {
                    $this->captchaStyle = 'popup';
                }
                break;
            case 'verify5':
                $extConfig['token'] = $this->getVerify5Token();
                $this->captchaStyle = 'default';
                break;
            case 'vaptcha':
                if (!$this->captchaStyle) {
                    $this->captchaStyle = 'invisible';
                }
                break;
            case 'wangyi':
                if ($this->captchaStyle === null) {
                    $this->captchaStyle = 'popup';
                }
                break;

            default:
                break;
        }

        return view('auth-captcha::' . $this->captchaProvider . '.' . $this->providerStyles[$this->captchaProvider][$this->captchaStyle], [
            'captchaAppid' => $this->captchaAppid,
            'captchaStyle' => $this->captchaStyle,
            'extConfig' => $extConfig,
        ]);
    }

    /**
     * Get Verify5 Token
     *
     * @return bool|string
     */
    private function getVerify5Token()
    {
        $params = [
            'appid' => $this->captchaAppid,
            'timestamp' => now()->timestamp . '000',
        ];
        $params['signature'] = $this->getSignature($this->captchaSecret, $params);
        $url = 'https://' . config('admin.extensions.auth-captcha.host') . '/openapi/getToken?' . http_build_query($params);
        $response = $this->newHttp()->get($url);
        $statusCode = $response->getStatusCode();
        $contents = $response->getBody()->getContents();
        if ($statusCode != 200) {
            return '';
        }
        $result = json_decode($contents, true);
        if ($result['success'] != true) {
            return '';
        }
        return $result['data']['token'];
    }

    /**
     * Post Login
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|mixed
     */
    public function postLogin(Request $request)
    {
        switch ($this->captchaProvider) {
            case 'dingxiang':
                return $this->captchaValidateDingxiang($request);
                break;
            case 'tencent':
                return $this->captchaValidateTencent($request);
                break;
            case 'verify5':
                return $this->captchaValidateVerify5($request);
                break;
            case 'vaptcha':
                return $this->captchaValidateVaptcha($request);
                break;
            case 'wangyi':
                return $this->captchaValidateWangyi($request);
                break;

            default:
                return back()->withInput()->withErrors(['captcha' => __('Config Error.')]);
                break;
        }
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
     * Verify5 Captcha
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     */
    public function captchaValidateVerify5(Request $request)
    {
        $token = $request->input('token', '');
        $verify5Token = $request->input('verify5_token', '');
        if (empty($token) || empty($verify5Token)) {
            return back()->withInput()->withErrors(['captcha' => __('Sliding validation failed. Please try again.')]);
        }

        $params = [
            'host' => config('admin.extensions.auth-captcha.host'),
            'verifyid' => $token,
            'token' => $verify5Token,
            'timestamp' => now()->timestamp . '000',
        ];
        $params['signature'] = $this->getSignature($this->captchaSecret, $params);
        $url = 'https://' . config('admin.extensions.auth-captcha.host') . '/openapi/verify?' . http_build_query($params);
        $response = $this->newHttp()->get($url);
        $statusCode = $response->getStatusCode();
        $contents = $response->getBody()->getContents();
        if ($statusCode != 200) {
            return back()->withInput()->withErrors(['captcha' => __('Sliding validation failed. Please try again.')]);
        }
        $result = json_decode($contents, true);
        if ($result['success'] != true) {
            return back()->withInput()->withErrors(['captcha' => __('Sliding validation failed. Please try again.')]);
        }

        return $this->loginValidate($request);
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

        $url = 'http://0.vaptcha.com/verify';
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
     * Wangyi Captcha
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     */
    private function captchaValidateWangyi(Request $request)
    {
        $token = $request->input('token', '');
        if (empty($token)) {
            return back()->withInput()->withErrors(['captcha' => __('Sliding validation failed. Please try again.')]);
        }

        $secretKey = config('admin.extensions.auth-captcha.secret_key', '');
        if (empty($secretKey)) {
            return back()->withInput()->withErrors(['captcha' => __('Config Error.')]);
        }

        $params = [
            'captchaId' => $this->captchaAppid,
            'validate' => $token,
            'user' => '',
            'secretId' => $this->captchaSecret,
            'version' => 'v2',
            'timestamp' => now()->timestamp . '000',
            'nonce' => str_random(),
        ];

        $params['signature'] = $this->getSignature($secretKey, $params);

        $url = 'http://c.dun.163yun.com/api/v2/verify';
        $response = $this->newHttp()->post($url, [
            'form_params' => $params,
        ]);
        $statusCode = $response->getStatusCode();
        $contents = $response->getBody()->getContents();

        if ($statusCode != 200) {
            return back()->withInput()->withErrors(['captcha' => __('Sliding validation failed. Please try again.')]);
        }
        $result = json_decode($contents, true);
        if ($result['result'] === true) {
            return $this->loginValidate($request);
        }

        return back()->withInput()->withErrors(['captcha' => __('Sliding validation failed. Please try again.')]);
    }

    /**
     * 网易生成签名信息
     *
     * @param $secretKey
     * @param $params
     * @return string
     */
    function getSignature($secretKey, $params)
    {
        ksort($params);
        $str = '';
        foreach ($params as $key => $value) {
            $str .= $key . $value;
        }
        $str .= $secretKey;
        return md5($str);
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