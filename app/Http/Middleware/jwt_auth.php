<?php

namespace App\Http\Middleware;

use App\Exceptions\ValidationErrorException;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
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
        $token = $request->bearerToken();
        
        try {
            $jwt_user = JWT_Token::CheckToken($token);
        } catch (\Throwable $th) {
            $message = $th->getMessage();
            if($message == 'Token Expired') {
                $new_token = JWT_Token::UpdateToken($token, '7 day');
                $jwt_user = JWT_Token::CheckToken($new_token);
                $request->new_token = $new_token;
            }else{
                throw new ValidationErrorException(['token' => $message]);
            }
        }

        $user = new User((array) $jwt_user);
        $user->id = $jwt_user->id;
        auth()->setUser($user);
        $request->setUserResolver(fn() => $user);
        return $next($request);
    }
}
