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
        $result = Comment::where('post_id', $postId)
        ->with(['user'])->latest()
        ->cursorPaginate(5,['id','user_id','post_id','content','created_at']);

        $result = $result->toArray();
        $comments = $result['data'];
        $nextCursor = $result['next_cursor']; 

        return response()->json(compact('comments', 'nextCursor'));
    }

    public function store(Request $request, Post $post)
    {
        $validated = $request->validate([
            'content' => 'required'
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
            'content' => 'required',
        ]);

        $comment->update($validated);

        return response()->json([
            'messsage' => 'comment Updated Successfully',
            'commentContent' => $comment->content
        ]);
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
