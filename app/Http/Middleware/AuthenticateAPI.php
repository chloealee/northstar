<?php namespace Northstar\Http\Middleware;

use Northstar\Models\ApiKey;
use Closure;
use Response;

class AuthenticateAPI
{

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $app_id = $request->header('X-DS-Application-Id');
        $api_key = $request->header('X-DS-REST-API-Key');

        if (!ApiKey::exists($app_id, $api_key)) {
            return Response::json("Unauthorized access.", 404);
        }

        return $next($request);
    }

}
