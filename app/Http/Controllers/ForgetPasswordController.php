<?php

namespace App\Http\Controllers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\ForgetPasswordOTPMail;

class ForgetPasswordController extends Controller 
{
    public function forgetPassword (Request $request) {
        $request->validate([
            'email' => 'required|email'
        ]);

        $user = User::where('email', $request->email)->first();

        if(!$user) {
            return response()->json([
                "errors" =>[
                    'email' =>  ["No User Found"]
                ],
            ],422);
        }

        $otp = fake()->randomNumber(6, true);
        
       Mail::to($request->email)->queue(new ForgetPasswordOTPMail($user, $otp));

        $user->forget_password_otp = $otp;
        $user->otp_updated_at = Carbon::now();
        $user->save();
        
        return response()->json([
            'message' => 'Email Exists',
        ],200);
    }


    public function checkOtp (Request $request) {
        $user = User::where('email', $request->email)->first();

        if(Carbon::create($user->otp_updated_at)->diffInMinutes(Carbon::now()) >= 10){
            $user->forget_password_otp = null;
            $user->save();

            return response()->json([
                "errors" =>[
                    'otp' =>  ["OTP Time Out"]
                ],
            ],422);
        } 
         
        if( $user->forget_password_otp == $request->otp){
            return response()->json([
                'message' => 'OTP Matches',
                'id' => $user->id
            ],200); 
        }else{
            return response()->json([
                "errors" =>[
                    'otp' =>  ["Wrong OTP"]
                ],
            ],422);
        }
    }  
    
    public function setNewPassword (Request $request) {
        $user = User::where('id', $request->id)->first();

        if(Carbon::create($user->otp_updated_at)->diffInMinutes(Carbon::now()) >= 10){
            return response()->json([
                "errors" =>[
                    'otp' =>  ["OTP Time Out"]
                ],
            ],422);
        } 

        $valdiated = $request->validate([
            'password' => 'required|min:4|confirmed'
        ]);

        $user->update($valdiated);

        return response()->json([
            'message' => 'Password reset Successfully',
        ],200); 
    }

}
