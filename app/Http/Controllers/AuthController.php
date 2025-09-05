<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function register(Request $request, User $user)
    {
        $data = $request->all();
        $validator = Validator::make($data, [
            'name' => 'required|string|max:100',
            'email' =>'required|email|unique:users,email|max:100',
            'password' => 'required|min:4|confirmed|max:100',
        ]);

        if($validator->fails() ){
            return response()->json(['errors' => $validator->messages()], 422);
        }

        $user = $user->create($data);

        return response()->json([
            'user' => $user,
            'message' => 'User created successfully',
        ]);
    }

    public function login(Request $request)
    {
        $data = $request->all();

        $validator = Validator::make($data, [
            'email' =>'required|email|max:100',
            'password' => 'required|min:4|max:100'
        ]);

        if($validator->fails()){
            return response()->json(['errors' => $validator->messages()], 422);
        }

        if (!Auth::attempt($data)) {
            return response()->json([
                "errors" =>[
                    'email' =>  ["Email or Password is Wrong"]
                ]
            ],422);
        }

        $user = $request->user();
        $token = $user->createToken('auth_token',['*'], now()->addDays(7))->plainTextToken;

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

    public function logout()
    {
        $user = request()->user(); 

        $user->currentAccessToken()->delete();

        return response()->json(['message' => 'Successfully logged out']);
    }

    public function changePassword (Request $request) {

        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|min:4|confirmed'
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'errors' => [
                    'current_password' => ['current password is wrong']
                ],
            ],401);
        }

        $user->password = bcrypt($request->new_password);
        $user->save();

        return response()->json([
            'message' => 'Password Updated Successfully',
        ],200);
    }

}
