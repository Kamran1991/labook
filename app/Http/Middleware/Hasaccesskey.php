<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use DB;

class Hasaccesskey
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {

        $res = DB::table('api_access_keys')->where('access_key', trim($request->header('x-api-key')))->first();
        
        if (!$res) {
            return response(['status' => false, 'message'=> 'Permission Denied'], 400);
        }

        return $next($request);
    }
}
