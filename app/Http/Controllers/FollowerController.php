<?php

namespace App\Http\Controllers;

use App\Models\Follower;
use App\Models\User;
use Illuminate\Http\Request;

class FollowerController extends Controller
{
    public function follow (User $user, Request $request) {

        $auth = $request->user()->id;

        if($user->id == $auth){
            return response()->json(
                ['error' => "can't follow your self" ]
            ,422);
        }

        $message = '';

        $exists = Follower::where('user_id', $user->id)
        ->where('follower_id', $auth)->first();

        if ($exists) {
            $exists->delete();
            $message = 'UnFollow';
            
        }else{
           $follows = Follower::create([
                'user_id' => $user->id, 
                'follower_id' => $auth
            ]);

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

