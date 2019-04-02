<?php
namespace Cuatromedios\Kusikusi\Http\Middleware;

use Closure;
use Cuatromedios\Kusikusi\Models\Http\ApiResponse;
use Illuminate\Contracts\Auth\Factory as Auth;

/**
 * Class Authenticate
 *
 * @package Cuatromedios\Kusikusi\Http\Middleware
 */
class Authenticate
{
    /**
     * The authentication guard factory instance.
     *
     * @var \Illuminate\Contracts\Auth\Factory
     */
    protected $auth;

    /**
     * Create a new middleware instance.
     *
     * @param  \Illuminate\Contracts\Auth\Factory $auth
     *
     * @return void
     */
    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @param  string|null $guard
     *
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        if ($this->auth->guard($guard)->guest()) {
            $response = new ApiResponse(null, null, ApiResponse::TEXT_UNAUTHORIZED, ApiResponse::STATUS_UNAUTHORIZED);

            return ($response)->response();
        }

        return $next($request);
    }
}
