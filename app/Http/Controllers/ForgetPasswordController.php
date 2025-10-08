<?php

namespace App\Http\Controllers;

use App\Exceptions\ValidationErrorException;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\ForgetPasswordOTPMail;
use Illuminate\Support\Facades\RateLimiter;

class ForgetPasswordController extends Controller 
{
    private const OTP_ALOWED_TIME = 60 * 15 ;
    public function findUserAndSendOTP (Request $request) 
    {
        $this->isValid($request,[
            'email' =>'required|email|max:100',
        ]);
      
        $user = User::select(['name','email'])->where('email', $request->email)->first();

        if(!$user) {
            throw new ValidationErrorException([
                'email' =>  ["User Not Found"]
            ]);
        }

        $this->isTooManyOTP_resend($request);

        return $this->createAndSendOTP($user);
    }

    private function isTooManyOTP_resend (Request $request) 
    {
        $key = "resend_otp_{$request->email}";
        $maxAttempts = 3;

        if(RateLimiter::tooManyAttempts($key, $maxAttempts)){
            $seconds = RateLimiter::availableIn($key);
            $minutes = ceil($seconds/60);
            throw new ValidationErrorException([
                'otp' => ["Too many attempts. Try again in {$minutes} minutes."]
            ],429);
        }

        RateLimiter::hit($key, self::OTP_ALOWED_TIME);
    }

    private function createAndSendOTP (User $user) 
    {
        $otp = rand(100000,999999);
        
        DB::table('password_reset_tokens')
        ->updateOrInsert(['email' => $user->email], ['token' => Hash::make($otp), 'created_at' => now()]);

        Mail::to($user->email)->queue(new ForgetPasswordOTPMail($user->name, $otp));

        return $this->response([
            'message' => 'Email Exists and otp sent successfully',
            'otp' => $otp //must deleted
        ]);
    }

    public function resendOTP (Request $request)  
    {
       return $this->findUserAndSendOTP($request);
    }

    public function checkOtp (Request $request) 
    {
        $this->isValid($request,[
            'email' =>'required|email|max:100',
            'otp' => 'required|digits:6',
        ]);
       
        return $this->check_otp_validation($request);
    } 
    
    private function check_otp_validation (Request $request) 
    {
        $otp_data = DB::table('password_reset_tokens')
        ->select(['token','created_at'])
        ->where('email', $request->email)->first();

        $this->is_otp_still_valid($otp_data);
        
        $otp = $otp_data->token;

        if(!Hash::check($request->otp,$otp)){
            throw new ValidationErrorException([
                'otp' => ["Wrong OTP"]
            ]);
        }

        return $this->response([
            'message' => 'OTP Matches',
        ]); 
    }

    private function is_otp_still_valid (object|null $otp_data) 
    {
        if(!$otp_data) {
            throw new ValidationErrorException([
                'otp' => ["No OTP Found For This Email"]
            ]);
        }

        $expires_at = strtotime($otp_data->created_at) + self::OTP_ALOWED_TIME;

        if(!$expires_at >= now()->timestamp) {
             throw new ValidationErrorException([
                'otp' => ["OTP Time Out"]
            ]);
        }        
    }

    public function isValidTokenExsists (Request $request)  
    {
        $this->isValid($request,[
            'email' =>'required|email|max:100',
            'new_password' => 'required|min:4|confirmed|max:100'
        ]);

        $otp_data = DB::table('password_reset_tokens')
        ->select(['created_at'])
        ->where('email', $request->email)->first();  
        
        $this->is_otp_still_valid($otp_data);
    }
    
    public function setNewPassword (Request $request) 
    {
        $this->isValidTokenExsists($request);

        DB::transaction(function () use ($request)
        {
            DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->where('token', $request->token)->delete();

            $new_password_hash = Hash::make($request->new_password);
            
            User::where('email', $request->email)
            ->update(['password' => $new_password_hash]);
        });
          
        return $this->response([
            'message' => 'Password reset Successfully',
        ]); 
    }

}
