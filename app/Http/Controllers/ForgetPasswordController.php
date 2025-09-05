<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\ForgetPasswordOTPMail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;

class ForgetPasswordController extends Controller 
{
    private const OTP_ALOWED_TIME = 60 * 15 ;
    public function findUserAndSendOTP (Request $request) 
    {
        $validator = Validator::make([ 'email' => $request->email ], [
            'email' =>'required|email|max:100',
        ]);

        if($validator->fails() )
            return response()->json(['errors' => $validator->messages()], 422);
        
      
        $user = User::select(['name','email'])->where('email', $request->email)->first();

        if(!$user) {
            return response()->json([
                "errors" =>[
                    'email' =>  ["No User Found"]
                ],
            ],422);
        }

        $is_OTP_resend_ExceededLimt = $this->isTooManyOTP_resend($request);
        if($is_OTP_resend_ExceededLimt) return $is_OTP_resend_ExceededLimt;

        return $this->createAndSendOTP($user);
    }

    private function createAndSendOTP (User $user) 
    {
        $otp = rand(100000,999999);
        
        DB::table('password_reset_tokens')
        ->updateOrInsert(['email' => $user->email], ['token' => Hash::make($otp), 'created_at' => now()]);

        Mail::to($user->email)->queue(new ForgetPasswordOTPMail($user->name, $otp));

        return response()->json([
            'message' => 'Email Exists and otp sent successfully',
            'otp' => $otp //must deleted
        ]);
    }

    public function resendOTP (Request $request)  
    {
       return $this->findUserAndSendOTP($request);
    }

    private function isTooManyOTP_resend (Request $request) 
    {
        $key = "resend_otp_{$request->email}";
        $maxAttempts = 3;

        if(RateLimiter::tooManyAttempts($key, $maxAttempts)){
            $seconds = RateLimiter::availableIn($key);
            $minutes = ceil($seconds/60);
            return response()->json([
                'errors' => [
                    'otp' => ["Too many attempts. Try again in {$minutes} minutes."]
                ]
            ], 429);
        }

        RateLimiter::hit($key, self::OTP_ALOWED_TIME);
        return false;
    }

    public function checkOtp (Request $request) 
    {
        $validator = Validator::make($request->all(), [
            'email' =>'required|email|max:100',
            'otp' => 'required|digits:6',
        ]);

        if($validator->fails() )
            return response()->json(['errors' => $validator->messages()], 422);


        return $this->check_otp_validation($request);
    } 
    
    private function check_otp_validation (Request $request) 
    {
        $otp_data = DB::table('password_reset_tokens')
        ->select(['token','created_at'])->where('email', $request->email)->first();

        $isExpiredError = $this->isStillValid($otp_data);
        if($isExpiredError) return $isExpiredError;
        
        $otp = $otp_data->token;

        if(!Hash::check($request->otp,$otp)){
            return response()->json([
                "errors" =>[
                    'otp' =>  ["Wrong OTP"]
                ],
            ],422);
        }

        return response()->json([
            'message' => 'OTP Matches',
            'token' => $otp //must delted
        ]); 
    }

    private function isStillValid (object $otp_data) 
    {
        if(!$otp_data) {
            return response()->json([
                "errors" =>[
                    'otp' =>  ["No OTP Found For This Email"]
                ],
            ],422);
        }

        $expires_at = strtotime($otp_data->created_at) + self::OTP_ALOWED_TIME;
        if(!$expires_at >= now()->timestamp) {
            return response()->json([
                "errors" =>[
                    'otp' =>  ["OTP Time Out"]
                ],
            ],422);
        }
        
        return false;
    }

    public function isValidTokenExsists (Request $request)  
    {
        $validator = Validator::make($request->all(), [
            'email' =>'required|email|max:100',
            'new_password' => 'required|min:4|confirmed|max:100'
        ]);

        if($validator->fails())
            return response()->json(['errors' => $validator->messages()], 422);

        $otp_data = DB::table('password_reset_tokens')
        ->select(['created_at'])
        ->where('email', $request->email)->first();  
        
        $isExpiredError = $this->isStillValid($otp_data);
        if($isExpiredError) return $isExpiredError;

        DB::table('password_reset_tokens')
        ->where('email', $request->email)
        ->where('token', $request->token)->delete();

        return false;
    }
    
    public function setNewPassword (Request $request) 
    {
        $isNotValidToken = $this->isValidTokenExsists($request);
        if($isNotValidToken) return $isNotValidToken;
        
        $user = User::select(['password'])->where('email', $request->email)->first();

        $user->update(['password' => $request->new_password]);
        
        return response()->json([
            'message' => 'Password reset Successfully',
        ],200); 
    }

}
