<?php

namespace App\Http\Controllers;

use App\Events\LikeEvent;
use App\Jobs\SendLikeNotifiction;
use App\Models\Post;
use \Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LikeController extends Controller
{
    public function like(string $postId ,Request $request) 
    {

        $message = '';
        $auth = $request->user();

        $post = Post::find($postId);
        
        $exists = DB::table('likes')
        ->where('user_id', $auth->id)
        ->where('post_id',$postId)->first();

        if ($exists) {
            $post->likes()->detach($auth->id);
            $message = 'Unliked';
        }else{
            $post->likes()->attach($auth->id);
            $message = 'Liked';
            SendLikeNotifiction::dispatchAfterResponse($postId, $auth);
        }

        $likes = DB::table('likes')->where('post_id', $postId)->count();
        broadcast(new LikeEvent($likes, $postId))->toOthers();
        
        return response()->json([
            'message' => $message,
            'likes' => $likes ?? 0
        ]);
    }

}
