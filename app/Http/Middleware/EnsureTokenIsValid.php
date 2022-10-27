<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;
use DB;
class EnsureTokenIsValid
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
        
        if (!$request->bearerToken()) {
            return response(['status' => false, 'message'=> 'Unauthorize'], 401);
        }
        
        $sql = "select * from users where access_token = '".$request->bearerToken()."'";
       
        $user = DB::select($sql);
        
        if (!$user) {
            return response(['status' => false, 'error'=> ['Unauthorize']], 401);
        }
        

        return $next($request);
    }
}
