<?php

namespace App\Http\Controllers;

use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Jobs\SendCommentNotifiction;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CommentController extends Controller
{
    public function index (string $postId)  
    {
        $comments = Comment::where('post_id', $postId)
        ->with(['user:id,name,img'])->latest()
        ->paginate(5,['id','user_id','post_id','comment','created_at']);

        $comments = $comments->toArray()['data'];
   
        return response()->json(compact('comments'));
    }

    public function store(Request $request, Post $post)
    {
        $validated = $request->validate([
            'comment' => 'required'
        ]);

        $validated['post_id'] = $post->id;
        $validated['user_id'] = $request->user()->id;

        $comment = Comment::create($validated);
        unset($comment->updated_at);
        
        SendCommentNotifiction::dispatchAfterResponse($post->id, $request->user(), $comment);

        return response()->json([
            'messsage' => 'comment Created Successfully',
            'comment' => $comment
        ]);    
    }


    public function update(Request $request, Comment $comment)
    {
        Gate::authorize('update', $comment);

        $validated = $request->validate([
            'comment' => 'required|min:1',
        ]);

        $comment->update($validated);

        $comment->load(['user:id,name,img']);

        return response([
            'messsage' => 'comment Updated Successfully',
            'comment' => new CommentResource($comment)
        ], 200);
    }

    public function destroy(Comment $comment)
    {
        Gate::authorize('delete', $comment);

        $comment->delete();

        return response()->json([
            'message' => 'Comment deleted successfully',
            'comment' => $comment
        ], 200);
    }
}
