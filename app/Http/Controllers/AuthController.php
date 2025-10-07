<?php

namespace App\Http\Controllers;

use App\Exceptions\ValidationErrorException;
use App\JWT_Token\JWT_Token;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $this->isValid($request, [
            'name' => 'required|string|max:100',
            'email' =>'required|email|unique:users,email|max:100',
            'password' => 'required|confirmed|min:4|max:100',
        ]);

        $user = User::create($data);

        unset($user->password, $user->updated_at);

        return $this->response([
            'message' => 'User created successfully',
            'user' => $user,
        ]);
    }

    public function login(Request $request)
    {
        $this->isValid($request, [
            'email' =>'required|email|max:100',
            'password' => 'required|min:4|max:100'
        ]);

        $user = User::select(['id', 'name', 'email', 'password', 'profile_image_id'])
        ->where('email', $request->email)->with(['profilePic'])->first();
        
        if(!$user || !Hash::check($request->password,$user->password)){
            throw new ValidationErrorException([
                'email' =>  ["Email or Password is Wrong"]
            ]);
        }

        $token = JWT_Token::CreatToken($user,'1 min');

        unset($user->password, $user->updated_at, $user->expires_at);

        return $this->response([
            'message' => 'User loggedin successfully',
            'user' =>  $user,
            'token' => $token
        ]);
    }

    // public function logout(Request $request)
    // {
    //     $request->user()->currentAccessToken()->delete();
    //     return response()->json(['message' => 'Successfully logged out']);
    // }

    public function changePassword (Request $request) 
    {
        $this->isValid($request, [
            'current_password' => 'required|string',
            'new_password' => 'required|min:4|confirmed'
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password,$user->password)) {
            throw new ValidationErrorException([
                'current_password' => ['current password is wrong']
            ]);
        }

        $user->update(['password' => $request->new_password]);

        $new_jwt_token = JWT_Token::CreatToken($user, '7 day');

        return $this->response([
            'message' => 'Password Updated Successfully',
            'new_jwt_token' => $new_jwt_token
        ]);
    }
}
