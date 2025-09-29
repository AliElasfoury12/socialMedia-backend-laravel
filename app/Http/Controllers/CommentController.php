<?php

namespace App\Http\Controllers;

use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Jobs\SendCommentNotifiction;
use App\Models\Post;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class CommentController extends Controller
{
    public function index (Request $request, int $postId)  
    {
        $comments = Comment::where('post_id', $postId)
        ->with(['user'])->latest()
        ->cursorPaginate(5,['id','user_id','post_id','content','created_at']);

        return response()->json([
            'comments' => $comments->items(),
            'nextCursor' => $comments->nextCursor()?->encode()
        ]);
    }

    public function store(Request $request, int $postId)
    {
        $data = $this->isValid($request,['content' => 'required|min:1|max:500']);       
        $data['user_id'] = $request->user()->id;
        $data['post_id'] = $postId;

        $comment = Comment::create($data);
        unset($comment->updated_at);

        SendCommentNotifiction::dispatchAfterResponse($postId, $request->user(), $comment);

        return response()->json([
            'messsage' => 'comment Created Successfully',
            'comment' => $comment
        ]);    
    }

    public function update(Request $request, string $commentId)
    {
        $data = $this->isValid($request,['content' => 'required|min:1|max:500']);       

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
