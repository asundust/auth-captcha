<?php

namespace Asundust\AuthCaptcha\Http\Controllers;

use Asundust\AuthCaptcha\AuthCaptcha;
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
        'recaptcha' => [
            'default' => 'login',
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
        'yunpian' => [
            'flat' => 'login_style',
            'float' => 'login_style',
            'dialog' => 'login_style',
            'external' => 'login_style',
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
            case 'recaptcha':
                if (!$this->captchaStyle) {
                    $this->captchaStyle = 'default';
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
            case 'yunpian':
                if (!$this->captchaStyle) {
                    $this->captchaStyle = 'dialog';
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
        $response = $this->captchaHttp()->get($url);
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
            case 'recaptcha':
                return $this->captchaValidateRecaptcha($request);
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
            case 'yunpian':
                return $this->captchaValidateYunpian($request);
                break;

            default:
                return back()->withInput()->withErrors(['captcha' => $this->getErrorMessage('config')]);
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
            return back()->withInput()->withErrors(['captcha' => $this->getErrorMessage('fail')]);
        }
        $tokenArr = array_filter(explode(':', $token));
        if (count($tokenArr) != 2) {
            return back()->withInput()->withErrors(['captcha' => $this->getErrorMessage('fail')]);
        }

        $params = [
            'appKey' => $this->captchaAppid,
            'constId' => $tokenArr[1],
            'sign' => md5($this->captchaSecret . $tokenArr[0] . $this->captchaSecret),
            'token' => $tokenArr[0],
        ];

        $url = 'https://cap.dingxiang-inc.com/api/tokenVerify';
        $response = $this->captchaHttp()->get($url . '?' . http_build_query($params));
        $statusCode = $response->getStatusCode();
        $contents = $response->getBody()->getContents();

        if ($statusCode != 200) {
            return back()->withInput()->withErrors(['captcha' => $this->getErrorMessage('fail')]);
        }
        $result = json_decode($contents, true);
        if ($result['success'] === true) {
            return $this->loginValidate($request);
        }
        return back()->withInput()->withErrors(['captcha' => $this->getErrorMessage('fail')]);
    }

    /**
     * Recaptcha Captcha
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     */
    private function captchaValidateRecaptcha(Request $request)
    {
        $token = $request->input('token', '');
        if (empty($token)) {
            return back()->withInput()->withErrors(['captcha' => $this->getErrorMessage('fail')]);
        }

        $params = [
            'secret' => $this->captchaSecret,
            'response' => $token,
            'remoteip' => $request->ip(),
        ];

        $url = rtrim(config('admin.extensions.auth-captcha.domain', 'https://recaptcha.net')) . '/recaptcha/api/siteverify';
        $response = $this->captchaHttp()->post($url, [
            'form_params' => $params,
        ]);
        $statusCode = $response->getStatusCode();
        $contents = $response->getBody()->getContents();
        if ($statusCode != 200) {
            return back()->withInput()->withErrors(['captcha' => $this->getErrorMessage('fail')]);
        }
        $result = json_decode($contents, true);
        if ($result['success'] === true && $result['score'] >= config('admin.extensions.auth-captcha.score', 0.7)) {
            return $this->loginValidate($request);
        }
        return back()->withInput()->withErrors(['captcha' => $this->getErrorMessage('fail')]);
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
            return back()->withInput()->withErrors(['captcha' => $this->getErrorMessage('fail')]);
        }

        $params = [
            'aid' => $this->captchaAppid,
            'AppSecretKey' => $this->captchaSecret,
            'Ticket' => $ticket,
            'Randstr' => $randstr,
            'UserIP' => $request->getClientIp()
        ];

        $url = 'https://ssl.captcha.qq.com/ticket/verify';
        $response = $this->captchaHttp()->get($url . '?' . http_build_query($params));
        $statusCode = $response->getStatusCode();
        $contents = $response->getBody()->getContents();

        if ($statusCode != 200) {
            return back()->withInput()->withErrors(['captcha' => $this->getErrorMessage('fail')]);
        }
        $result = json_decode($contents, true);
        if ($result['response'] != 1) {
            return back()->withInput()->withErrors(['captcha' => $this->getErrorMessage('fail')]);
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
            return back()->withInput()->withErrors(['captcha' => $this->getErrorMessage('fail')]);
        }

        $params = [
            'host' => config('admin.extensions.auth-captcha.host'),
            'verifyid' => $token,
            'token' => $verify5Token,
            'timestamp' => now()->timestamp . '000',
        ];
        $params['signature'] = $this->getSignature($this->captchaSecret, $params);
        $url = 'https://' . config('admin.extensions.auth-captcha.host') . '/openapi/verify?' . http_build_query($params);
        $response = $this->captchaHttp()->get($url);
        $statusCode = $response->getStatusCode();
        $contents = $response->getBody()->getContents();
        if ($statusCode != 200) {
            return back()->withInput()->withErrors(['captcha' => $this->getErrorMessage('fail')]);
        }
        $result = json_decode($contents, true);
        if ($result['success'] != true) {
            return back()->withInput()->withErrors(['captcha' => $this->getErrorMessage('fail')]);
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
            return back()->withInput()->withErrors(['captcha' => $this->getErrorMessage('fail')]);
        }

        $params = [
            'id' => $this->captchaAppid,
            'secretkey' => $this->captchaSecret,
            'token' => $token,
            'ip' => $request->ip(),
        ];

        $url = 'http://0.vaptcha.com/verify';
        $response = $this->captchaHttp()->post($url, [
            'form_params' => $params,
        ]);
        $statusCode = $response->getStatusCode();
        $contents = $response->getBody()->getContents();

        if ($statusCode != 200) {
            return back()->withInput()->withErrors(['captcha' => $this->getErrorMessage('fail')]);
        }
        $result = json_decode($contents, true);
        if ($result['success'] != 1) {
            return back()->withInput()->withErrors(['captcha' => $this->getErrorMessage('fail')]);
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
            return back()->withInput()->withErrors(['captcha' => $this->getErrorMessage('fail')]);
        }

        $secretKey = config('admin.extensions.auth-captcha.secret_key', '');
        if (empty($secretKey)) {
            return back()->withInput()->withErrors(['captcha' => $this->getErrorMessage('config')]);
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
        $response = $this->captchaHttp()->post($url, [
            'form_params' => $params,
        ]);
        $statusCode = $response->getStatusCode();
        $contents = $response->getBody()->getContents();

        if ($statusCode != 200) {
            return back()->withInput()->withErrors(['captcha' => $this->getErrorMessage('fail')]);
        }
        $result = json_decode($contents, true);
        if ($result['result'] === true) {
            return $this->loginValidate($request);
        }

        return back()->withInput()->withErrors(['captcha' => $this->getErrorMessage('fail')]);
    }

    /**
     * Yunpian Captcha
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     */
    private function captchaValidateYunpian(Request $request)
    {
        $token = $request->input('token', '');
        $authenticate = $request->input('authenticate', '');
        if (empty($token) || empty($authenticate)) {
            return back()->withInput()->withErrors(['captcha' => $this->getErrorMessage('fail')]);
        }

        $secretKey = config('admin.extensions.auth-captcha.secret_key', '');
        if (empty($secretKey)) {
            return back()->withInput()->withErrors(['captcha' => $this->getErrorMessage('config')]);
        }

        $params = [
            'authenticate' => $authenticate,
            'captchaId' => $this->captchaAppid,
            'token' => $token,
            'secretId' => $this->captchaSecret,
            'user' => '',
            'version' => '1.0',
            'timestamp' => now()->timestamp . '000',
            'nonce' => str_random(),
        ];

        $params['signature'] = $this->getSignature($secretKey, $params);

        $url = 'https://captcha.yunpian.com/v1/api/authenticate';
        $response = $this->captchaHttp()->post($url, [
            'form_params' => $params,
        ]);
        $statusCode = $response->getStatusCode();
        $contents = $response->getBody()->getContents();
        if ($statusCode != 200) {
            return back()->withInput()->withErrors(['captcha' => $this->getErrorMessage('fail')]);
        }
        $result = json_decode($contents, true);
        if ($result['code'] === 0 && $result['msg'] == 'ok') {
            return $this->loginValidate($request);
        }

        return back()->withInput()->withErrors(['captcha' => $this->getErrorMessage('fail')]);
    }

    /**
     * 生成签名信息
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
    private function captchaHttp()
    {
        return new Client([
            'timeout' => 5,
            'verify' => false,
            'http_errors' => false,
        ]);
    }

    /**
     * getErrorMessage
     *
     * @param $type
     * @return array|string|null
     */
    private function getErrorMessage($type)
    {
        switch ($type) {
            case 'fail':
                return __('Sliding validation failed. Please try again.');
                break;

            case 'config':
                return __('Config Error.');
                break;

            default:
                return __('Error');
                break;
        }
    }
}