<?php

namespace Asundust\AuthCaptcha\Http\Middleware;

use Closure;
use Illuminate\Routing\Middleware\ThrottleRequests;

class AuthCaptchaThrottleMiddleware extends ThrottleRequests
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Illuminate\Http\Exceptions\ThrottleRequestsException
     */
    protected function handleRequest($request, Closure $next, array $limits)
    {
        foreach ($limits as $limit) {
            if ($this->limiter->tooManyAttempts($limit->key, $limit->maxAttempts)) {
                return $this->buildException($request, $limit->key, $limit->maxAttempts, $limit->responseCallback);
            }

            $this->limiter->hit($limit->key, $limit->decayMinutes * 60);
        }

        $response = $next($request);

        foreach ($limits as $limit) {
            $response = $this->addHeaders(
                $response,
                $limit->maxAttempts,
                $this->calculateRemainingAttempts($limit->key, $limit->maxAttempts)
            );
        }

        return $response;
    }

    /**
     * Create a 'too many attempts' exception.
     *
     * @param $request
     * @param $key
     * @param $maxAttempts
     * @param $responseCallback
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function buildException($request, $key, $maxAttempts, $responseCallback = null)
    {
        $retryAfter = $this->getTimeUntilNextRetry($key);

        $headers = $this->getHeaders(
            $maxAttempts,
            $this->calculateRemainingAttempts($key, $maxAttempts, $retryAfter),
            $retryAfter
        );

        return $this->captchaErrorResponse($this->getMessage());
    }

    private function getMessage(): array|string|\Illuminate\Contracts\Translation\Translator|\Illuminate\Contracts\Foundation\Application|null
    {
        return __('Too Many Attempts.');
    }

    /**
     *CaptchaErrorResponse.
     *
     * @param $message
     */
    public function captchaErrorResponse($message): \Illuminate\Http\RedirectResponse
    {
        return back()->withInput()->withErrors(['captcha' => $message]);
    }
}
