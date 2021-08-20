<?php

namespace Asundust\AuthCaptcha\Http\Controllers;

use Asundust\AuthCaptcha\AuthCaptcha;
use Encore\Admin\Controllers\AuthController as BaseAuthController;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Jenssegers\Agent\Facades\Agent;

class AuthCaptchaController extends BaseAuthController
{
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
        'geetest' => [
            'bind' => 'login',
            'float' => 'login_style',
            'popup' => 'login_style',
            'custom' => 'login_style',
        ],
        'hcaptcha' => [
            'invisible' => 'login',
            'display' => 'login_style',
        ],
        'recaptchav2' => [
            'invisible' => 'login',
            'display' => 'login_style',
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
     * Get Login.
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
            case 'geetest':
                if (!$this->captchaStyle) {
                    $this->captchaStyle = 'bind';
                }
                $extConfig = $this->getGeetestStatus();

                break;
            case 'hcaptcha':
            case 'recaptchav2':
            case 'vaptcha':
                if (!$this->captchaStyle) {
                    $this->captchaStyle = 'invisible';
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
            case 'wangyi':
                if (null === $this->captchaStyle) {
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

        return view('auth-captcha::'.$this->captchaProvider.'.'.$this->providerStyles[$this->captchaProvider][$this->captchaStyle], [
            'captchaAppid' => $this->captchaAppid,
            'captchaStyle' => $this->captchaStyle,
            'extConfig' => $extConfig,
        ]);
    }

    /**
     * Get Geetest Status.
     *
     * @return array
     */
    private function getGeetestStatus()
    {
        $clientType = Agent::isMobile() ? 'h5' : 'web';
        session(['GeetestAuth-client_type' => $clientType]);
        $params = [
            'client_type' => $clientType,
            'gt' => $this->captchaAppid,
            'ip_address' => request()->ip(),
            'new_captcha' => 1,
            'user_id' => '',
        ];
        $url = 'http://api.geetest.com/register.php?'.http_build_query($params);
        $response = $this->captchaHttp()->get($url);
        $statusCode = $response->getStatusCode();
        $contents = $response->getBody()->getContents();
        if (200 != $statusCode) {
            return $this->geetestFailProcess();
        }
        if (32 != strlen($contents)) {
            return $this->geetestFailProcess();
        }

        return $this->geetestSuccessProcess($contents);
    }

    /**
     * Geetest Success Process.
     *
     * @param $challenge
     *
     * @return array
     */
    private function geetestSuccessProcess($challenge)
    {
        $challenge = md5($challenge.$this->captchaSecret);
        $result = [
            'success' => 1,
            'gt' => $this->captchaAppid,
            'challenge' => $challenge,
            'new_captcha' => 1,
        ];
        session(['GeetestAuth-gtserver' => 1, 'GeetestAuth-user_id' => '']);

        return $result;
    }

    /**
     * Geetest Fail Process.
     *
     * @return array
     */
    private function geetestFailProcess()
    {
        $rnd1 = md5(rand(0, 100));
        $rnd2 = md5(rand(0, 100));
        $challenge = $rnd1.substr($rnd2, 0, 2);
        $result = [
            'success' => 0,
            'gt' => $this->captchaAppid,
            'challenge' => $challenge,
            'new_captcha' => 1,
        ];
        session(['GeetestAuth-gtserver' => 0, 'GeetestAuth-user_id' => 0]);

        return $result;
    }

    /**
     * Get Verify5 Token.
     *
     * @return bool|string
     */
    private function getVerify5Token()
    {
        $params = [
            'appid' => $this->captchaAppid,
            'timestamp' => now()->timestamp.'000',
        ];
        $params['signature'] = $this->getSignature($this->captchaSecret, $params);
        $url = 'https://'.config('admin.extensions.auth-captcha.host').'/openapi/getToken?'.http_build_query($params);
        $response = $this->captchaHttp()->get($url);
        $statusCode = $response->getStatusCode();
        $contents = $response->getBody()->getContents();
        if (200 != $statusCode) {
            return '';
        }
        $result = json_decode($contents, true);
        if (true != $result['success']) {
            return '';
        }

        return $result['data']['token'];
    }

    /**
     * Post Login.
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|mixed
     */
    public function postLogin(Request $request)
    {
        switch ($this->captchaProvider) {
            case 'dingxiang':
                return $this->captchaValidateDingxiang($request);

                break;
            case 'geetest':
                return $this->captchaValidateGeetest($request);

                break;
            case 'hcaptcha':
                return $this->captchaValidateHCaptcha($request);

                break;
            case 'recaptchav2':
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
     * Dingxiang Captcha.
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     */
    private function captchaValidateDingxiang(Request $request)
    {
        $token = $request->input('token', '');
        if (!$token) {
            return back()->withInput()->withErrors(['captcha' => $this->getErrorMessage('fail')]);
        }
        $tokenArr = array_filter(explode(':', $token));
        if (2 != count($tokenArr)) {
            return back()->withInput()->withErrors(['captcha' => $this->getErrorMessage('fail')]);
        }

        $params = [
            'appKey' => $this->captchaAppid,
            'constId' => $tokenArr[1],
            'sign' => md5($this->captchaSecret.$tokenArr[0].$this->captchaSecret),
            'token' => $tokenArr[0],
        ];

        $url = 'https://cap.dingxiang-inc.com/api/tokenVerify';
        $response = $this->captchaHttp()->get($url.'?'.http_build_query($params));
        $statusCode = $response->getStatusCode();
        $contents = $response->getBody()->getContents();

        if (200 != $statusCode) {
            return back()->withInput()->withErrors(['captcha' => $this->getErrorMessage('fail')]);
        }
        $result = json_decode($contents, true);
        if (true === $result['success']) {
            return $this->loginValidate($request);
        }

        return back()->withInput()->withErrors(['captcha' => $this->getErrorMessage('fail')]);
    }

    /**
     * Geetest Captcha.
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     */
    public function captchaValidateGeetest(Request $request)
    {
        $geetestChallenge = $request->input('geetest_challenge', '');
        $geetestValidate = $request->input('geetest_validate', '');
        $geetestSeccode = $request->input('geetest_seccode', '');
        if (!$geetestChallenge || !$geetestValidate || !$geetestSeccode) {
            return back()->withInput()->withErrors(['captcha' => $this->getErrorMessage('fail')]);
        }

        if (1 != session('GeetestAuth-gtserver')) {
            if (md5($geetestChallenge) == $geetestValidate) {
                return $this->loginValidate($request);
            }

            return back()->withInput()->withErrors(['captcha' => $this->getErrorMessage('fail')]);
        }

        $params = [
            'challenge' => $geetestChallenge,
            'client_type' => session('GeetestAuth-client_type'),
            'gt' => $this->captchaAppid,
            'ip_address' => $request->ip(),
            'json_format' => 1,
            'new_captcha' => 1,
            'sdk' => 'php_3.0.0',
            'seccode' => $geetestSeccode,
            'user_id' => session('GeetestAuth-user_id'),
            'validate' => $geetestValidate,
        ];

        $url = 'http://api.geetest.com/validate.php';
        $response = $this->captchaHttp()->post($url, [
            'form_params' => $params,
        ]);
        $statusCode = $response->getStatusCode();
        $contents = $response->getBody()->getContents();
        if (200 != $statusCode) {
            return back()->withInput()->withErrors(['captcha' => $this->getErrorMessage('fail')]);
        }
        $result = json_decode($contents, true);
        if (is_array($result) && $result['seccode'] == md5($geetestSeccode)) {
            return $this->loginValidate($request);
        }

        return back()->withInput()->withErrors(['captcha' => $this->getErrorMessage('fail')]);
    }

    /**
     * HCaptcha Captcha.
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     */
    private function captchaValidateHCaptcha(Request $request)
    {
        $token = $request->input('token', '');
        if (!$token) {
            return back()->withInput()->withErrors(['captcha' => $this->getErrorMessage('fail')]);
        }

        $params = [
            'secret' => $this->captchaSecret,
            'response' => $token,
            'remoteip' => $request->ip(),
        ];

        $url = 'https://hcaptcha.com/siteverify';
        $response = $this->captchaHttp()->post($url, [
            'form_params' => $params,
        ]);
        $statusCode = $response->getStatusCode();
        $contents = $response->getBody()->getContents();
        if (200 != $statusCode) {
            return back()->withInput()->withErrors(['captcha' => $this->getErrorMessage('fail')]);
        }
        $result = json_decode($contents, true);
        if (true === $result['success']) {
            return $this->loginValidate($request);
        }

        return back()->withInput()->withErrors(['captcha' => $this->getErrorMessage('fail')]);
    }

    /**
     * Recaptcha Captcha.
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     */
    private function captchaValidateRecaptcha(Request $request)
    {
        $token = $request->input('token', '');
        if (!$token) {
            return back()->withInput()->withErrors(['captcha' => $this->getErrorMessage('fail')]);
        }

        $params = [
            'secret' => $this->captchaSecret,
            'response' => $token,
            'remoteip' => $request->ip(),
        ];

        $url = rtrim(config('admin.extensions.auth-captcha.domain', 'https://recaptcha.net')).'/recaptcha/api/siteverify';
        $response = $this->captchaHttp()->post($url, [
            'form_params' => $params,
        ]);
        $statusCode = $response->getStatusCode();
        $contents = $response->getBody()->getContents();
        if (200 != $statusCode) {
            return back()->withInput()->withErrors(['captcha' => $this->getErrorMessage('fail')]);
        }
        $result = json_decode($contents, true);
        if ('recaptcha' == $this->captchaProvider) {
            if (true === $result['success'] && $result['score'] >= config('admin.extensions.auth-captcha.score', 0.7)) {
                return $this->loginValidate($request);
            }
        } else {
            if (true === $result['success']) {
                return $this->loginValidate($request);
            }
        }

        return back()->withInput()->withErrors(['captcha' => $this->getErrorMessage('fail')]);
    }

    /**
     * Tencent Captcha.
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     */
    private function captchaValidateTencent(Request $request)
    {
        $ticket = $request->input('ticket', '');
        $randstr = $request->input('randstr', '');
        if (!$ticket || !$randstr) {
            return back()->withInput()->withErrors(['captcha' => $this->getErrorMessage('fail')]);
        }

        $params = [
            'aid' => $this->captchaAppid,
            'AppSecretKey' => $this->captchaSecret,
            'Ticket' => $ticket,
            'Randstr' => $randstr,
            'UserIP' => $request->getClientIp(),
        ];

        $url = 'https://ssl.captcha.qq.com/ticket/verify';
        $response = $this->captchaHttp()->get($url.'?'.http_build_query($params));
        $statusCode = $response->getStatusCode();
        $contents = $response->getBody()->getContents();

        if (200 != $statusCode) {
            return back()->withInput()->withErrors(['captcha' => $this->getErrorMessage('fail')]);
        }
        $result = json_decode($contents, true);
        if (1 != $result['response']) {
            return back()->withInput()->withErrors(['captcha' => $this->getErrorMessage('fail')]);
        }

        return $this->loginValidate($request);
    }

    /**
     * Verify5 Captcha.
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     */
    public function captchaValidateVerify5(Request $request)
    {
        $token = $request->input('token', '');
        $verify5Token = $request->input('verify5_token', '');
        if (!$token || !$verify5Token) {
            return back()->withInput()->withErrors(['captcha' => $this->getErrorMessage('fail')]);
        }

        $params = [
            'host' => config('admin.extensions.auth-captcha.host'),
            'verifyid' => $token,
            'token' => $verify5Token,
            'timestamp' => now()->timestamp.'000',
        ];
        $params['signature'] = $this->getSignature($this->captchaSecret, $params);
        $url = 'https://'.config('admin.extensions.auth-captcha.host').'/openapi/verify?'.http_build_query($params);
        $response = $this->captchaHttp()->get($url);
        $statusCode = $response->getStatusCode();
        $contents = $response->getBody()->getContents();
        if (200 != $statusCode) {
            return back()->withInput()->withErrors(['captcha' => $this->getErrorMessage('fail')]);
        }
        $result = json_decode($contents, true);
        if (true != $result['success']) {
            return back()->withInput()->withErrors(['captcha' => $this->getErrorMessage('fail')]);
        }

        return $this->loginValidate($request);
    }

    /**
     * Vaptcha Captcha.
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     */
    private function captchaValidateVaptcha(Request $request)
    {
        $token = $request->input('token', '');
        if (!$token) {
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

        if (200 != $statusCode) {
            return back()->withInput()->withErrors(['captcha' => $this->getErrorMessage('fail')]);
        }
        $result = json_decode($contents, true);
        if (1 != $result['success']) {
            return back()->withInput()->withErrors(['captcha' => $this->getErrorMessage('fail')]);
        }

        return $this->loginValidate($request);
    }

    /**
     * Wangyi Captcha.
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     */
    private function captchaValidateWangyi(Request $request)
    {
        $token = $request->input('token', '');
        if (!$token) {
            return back()->withInput()->withErrors(['captcha' => $this->getErrorMessage('fail')]);
        }

        $secretKey = config('admin.extensions.auth-captcha.secret_key', '');
        if (!$secretKey) {
            return back()->withInput()->withErrors(['captcha' => $this->getErrorMessage('config')]);
        }

        $params = [
            'captchaId' => $this->captchaAppid,
            'validate' => $token,
            'user' => '',
            'secretId' => $this->captchaSecret,
            'version' => 'v2',
            'timestamp' => now()->timestamp.'000',
            'nonce' => str_random(),
        ];

        $params['signature'] = $this->getSignature($secretKey, $params);

        $url = 'http://c.dun.163yun.com/api/v2/verify';
        $response = $this->captchaHttp()->post($url, [
            'form_params' => $params,
        ]);
        $statusCode = $response->getStatusCode();
        $contents = $response->getBody()->getContents();

        if (200 != $statusCode) {
            return back()->withInput()->withErrors(['captcha' => $this->getErrorMessage('fail')]);
        }
        $result = json_decode($contents, true);
        if (true === $result['result']) {
            return $this->loginValidate($request);
        }

        return back()->withInput()->withErrors(['captcha' => $this->getErrorMessage('fail')]);
    }

    /**
     * Yunpian Captcha.
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     */
    private function captchaValidateYunpian(Request $request)
    {
        $token = $request->input('token', '');
        $authenticate = $request->input('authenticate', '');
        if (!$token || !$authenticate) {
            return back()->withInput()->withErrors(['captcha' => $this->getErrorMessage('fail')]);
        }

        $secretKey = config('admin.extensions.auth-captcha.secret_key', '');
        if (!$secretKey) {
            return back()->withInput()->withErrors(['captcha' => $this->getErrorMessage('config')]);
        }

        $params = [
            'authenticate' => $authenticate,
            'captchaId' => $this->captchaAppid,
            'token' => $token,
            'secretId' => $this->captchaSecret,
            'user' => '',
            'version' => '1.0',
            'timestamp' => now()->timestamp.'000',
            'nonce' => str_random(),
        ];

        $params['signature'] = $this->getSignature($secretKey, $params);

        $url = 'https://captcha.yunpian.com/v1/api/authenticate';
        $response = $this->captchaHttp()->post($url, [
            'form_params' => $params,
        ]);
        $statusCode = $response->getStatusCode();
        $contents = $response->getBody()->getContents();
        if (200 != $statusCode) {
            return back()->withInput()->withErrors(['captcha' => $this->getErrorMessage('fail')]);
        }
        $result = json_decode($contents, true);
        if (0 === $result['code'] && 'ok' == $result['msg']) {
            return $this->loginValidate($request);
        }

        return back()->withInput()->withErrors(['captcha' => $this->getErrorMessage('fail')]);
    }

    /**
     * 生成签名信息.
     *
     * @param $secretKey
     * @param $params
     *
     * @return string
     */
    public function getSignature($secretKey, $params)
    {
        ksort($params);
        $str = '';
        foreach ($params as $key => $value) {
            $str .= $key.$value;
        }
        $str .= $secretKey;

        return md5($str);
    }

    /**
     * Login Validate.
     *
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
     * Http.
     *
     * @return Client
     */
    private function captchaHttp()
    {
        return new Client([
            'timeout' => config('admin.extensions.auth-captcha.timeout', 5),
            'verify' => false,
            'http_errors' => false,
        ]);
    }

    /**
     * getErrorMessage.
     *
     * @param $type
     *
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
