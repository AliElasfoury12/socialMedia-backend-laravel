<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FollowerController extends Controller
{
    public function follow (User $user, Request $request) {

        $authId = $request->user()->id;

        if($user->id == $authId){
            return response()->json(
                ['error' => "can't follow your self" ]
            ,422);
        }

        $message = '';

        $exists = DB::table('followers')->where('user_id', $user->id)
        ->where('follower_id', $authId)->first();

        if ($exists) {
            $user->followers()->detach($authId);
            $message = 'UnFollow';
            
        }else{
            $user->followers()->attach($authId);
            $message = 'Follow';
        }

        $user->load(['follows'])->loadCount(['followers', 'followings']);


        return response()->json([
            'message' => $message,
            'followers_count' => $user->followers_count ?? 0,
            'followings_count' => $user->followings_count ?? 0,
            'follows' => count($user->follows)
        ]);
    }

}

