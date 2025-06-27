<?php

namespace App\Http\Controllers;

use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Jobs\SendCommentNotifiction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CommentController extends Controller
{
    public function show(string $id)
    {
       $comments = Comment::where('post_id', $id)
       ->with(['user:id,name,img'])->latest()
       ->paginate(10,['id','user_id','post_id','comment','created_at']);
       
       return response()->json(
             CommentResource::collection($comments)
       , 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'comment' => 'required|min:1',
        ]);

        $validated['post_id'] = $request->postId;
        $validated['user_id'] = $request->user()->id;

        $comment = Comment::create($validated);

        $comment->load(['user:id,name,img']);

        SendCommentNotifiction::dispatchAfterResponse($request->postId, $request->user(), $comment);

        return response([
            'messsage' => 'comment Created Successfully',
            'comment' => new CommentResource($comment)
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
