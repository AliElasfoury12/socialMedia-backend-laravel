<?php

namespace App\Http\Controllers;

use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Jobs\SendCommentNotifiction;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

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

    public function store(Request $request, int $postId)
    {
        $data = [
            'content' => $request['content']
        ];

        $validator = Validator::make($data, [
            'content' => 'required|min:1|max:500'
        ]);

        if($validator->fails())
            return response()->json(['errors' => $validator->messages()], 422);
        
        $data['post_id'] = $postId;
        $data['user_id'] = $request->user()->id;

        $comment = Comment::create($data);
        
        SendCommentNotifiction::dispatchAfterResponse($postId, $request->user(), $comment);

        return response()->json([
            'messsage' => 'comment Created Successfully',
            'comment' => $comment
        ]);    
    }

    public function update(Request $request, string $commentId)
    {
       $data = [
            'content' => $request['content']
        ];

        $validator = Validator::make($data, [
            'content' => 'required|min:1|max:500'
        ]);

        if($validator->fails())
            return response()->json(['errors' => $validator->messages()], 422);

        Comment::where('id', $commentId)
        ->where('user_id', $request->user()->id)
        ->update($data);

        return response()->json([
            'messsage' => 'comment Updated Successfully',
            'commentContent' => $request['content']
        ]);
    }

    public function destroy(Comment $comment)
    {
        Gate::authorize('delete', $comment);

        $comment->delete();

        return response()->json([
            'message' => 'Comment deleted successfully',
            'comment' => $comment
        ]);
    }
}
