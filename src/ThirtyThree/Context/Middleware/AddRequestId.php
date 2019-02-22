<?php

namespace ThirtyThree\Context\Middleware;

use Closure;

class AddRequestId
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        $response->headers->set('X-Request-Id', app('context')->id());

        return $response;
    }
}
