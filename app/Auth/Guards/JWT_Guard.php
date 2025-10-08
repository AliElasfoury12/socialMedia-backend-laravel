<?php 

namespace App\Auth\Guards;

use App\JWT_Token\JWT_Token;
use App\Models\User;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;

class JWT_Guard implements Guard 
{
    protected $user = null;
    protected Request $request;

    public function __construct(Request $request) {
        $this->request = $request;
    }
    public function check(): bool  
    {
        return $this->user() !== null;
    }

    public function guest ()  
    {
    
    }

    public function hasUser ()  
    {
    
    }

    public function id ()  
    {
        return $this->user()?->id ?? null;
    }

    public function user ()  
    {
        if($this->user) return $this->user;

        $token = $this->request->bearerToken();
        if(!$token) return null;

        try {
            $this->create_user($token);
        } catch (\Throwable $th) {
            $message = $th->getMessage();
            if($message == 'Token Expired') {
                $new_token = JWT_Token::UpdateToken($token, '7 day');
                $this->create_user($new_token);
                $this->request->new_token = $new_token;
            }else{
                return null;
            }
        }

        return $this->user;
    }

    public function setUser(Authenticatable $user)   
    {
        $this->user = $user;
    }

    public function validate(array $credentials = [])   
    {
        return false;
    }

    private function create_user (string $token) 
    {
        $user = JWT_Token::CheckToken($token);
        $this->user = new User(attributes: (array) $user);
        $this->user->id = $user->id;
        $this->user->exists = true;
        $this->user->syncOriginal();
    }
}