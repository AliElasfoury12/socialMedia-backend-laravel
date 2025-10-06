<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Jobs\SendCommentNotifiction;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function index (int $postId)  
    {
        $comments = Comment::where('post_id', $postId)
        ->with(['user'])->orderByDesc('id')
        ->cursorPaginate(5,['id','user_id','content','created_at']);

        return $this->response([
            'comments' => $comments->items(),
            'nextCursor' => $comments->nextCursor()?->encode()
        ]);
    }

    public function store(Request $request, int $post_id)
    {
        $data = $this->isValid($request,['content' => 'required|min:1|max:500']);
        
        $user = $request->user();
        $data['user_id'] = $user->id;
        $data['post_id'] = $post_id;

        $comment = Comment::create($data);

        unset($comment->updated_at);

        $data = [
            'post_id' => $post_id,
            'user' => $user,
            'comment' => $comment
        ];

        SendCommentNotifiction::dispatchAfterResponse($data);

        return $this->response([
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

        return $this->response([
            'messsage' => 'comment Updated Successfully',
            'commentContent' => $request['content']
        ]);
    }

    public function destroy(int $comment_id)
    {
        Comment::where('id', $comment_id)
        ->where('user_id', auth()->id())
        ->delete();

        return $this->response([
            'message' => 'Comment deleted successfully',
        ]);
    }
}
