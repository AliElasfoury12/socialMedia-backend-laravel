<?php

namespace App\Http\Controllers;

use App\JWT_Token\JWT_Token;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

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

        return response()->json([
            'user' => $user,
            'message' => 'User created successfully',
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
        
        if(!$user || !Hash::check($request->password, $user->password)){
            return response()->json([
                "errors" =>[
                    'password' =>  ["Email or Password is Wrong"]
                ]
            ],422);
        }

        $jwt = new JWT_Token;
        $token = $jwt->CreatToken($user->toArray(),7 * 24 * 60 * 60 );
        //$token = $user->createToken('auth_token',['*'], now()->addDays(7))->plainTextToken;

        unset($user->password, $user->profile_image_id);

        return response()->json([
            'message' => 'User loggedin successfully',
            'user' =>  $user,
            'token' => $token
        ]);
    }

    public function user (Request $request) 
    {
        return $request->user()->toJson();
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Successfully logged out']);
    }

    public function changePassword (Request $request) {

        $this->isValid($request, [
            'current_password' => 'required|string',
            'new_password' => 'required|min:4|confirmed'
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password,$user->password)) {
            return response()->json([
                'errors' => [
                    'current_password' => ['current password is wrong']
                ],
            ],401);
        }

        $user->update(['password' => $request->new_password]);

        return response()->json([
            'message' => 'Password Updated Successfully',
        ]);
    }

}
