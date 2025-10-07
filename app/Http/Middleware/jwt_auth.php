<?php

namespace App\Http\Middleware;

use App\Exceptions\ValidationErrorException;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use App\JWT_Token\JWT_Token;

class jwt_auth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if(!Auth::guard('api')->check()){
            return response()->json(['error' => 'Unauthorized'],401);
        }
        return $next($request);
    }
}
