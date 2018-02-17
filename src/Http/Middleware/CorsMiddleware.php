<?php namespace App\Http\Middleware;

// https://gist.github.com/danharper/06d2386f0b826b669552

class CorsMiddleware {
    public function handle($request, \Closure $next)
    {
        $response = $next($request);
        $response->header('Access-Control-Allow-Methods', 'HEAD, GET, POST, PUT, PATCH, DELETE');
        $response->header('Access-Control-Allow-Headers', $request->header('Access-Control-Request-Headers'));
        $response->header('Access-Control-Allow-Origin', '*');
        // TODO: Maybe restrict all access based on configuration?
        return $response;
    }
}