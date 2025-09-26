<?php

namespace App\Jobs;

use App\Models\Post;
use App\Models\User;
use App\Notifications\CommentNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendCommentNotifiction implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public $postId;

    public $auth;

    public $comment;
    public function __construct($postId, $auth, $comment)
    {
        $this->postId = $postId; 
        $this->auth = $auth;
        $this->comment = $comment;  
        
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $post = Post::find($this->postId);

        if($post->user_id == $this->auth->id) return;

           $postUser = User::find($post->user_id);
           $title = $this->auth->name. ' ' . 'Commented on Your Post';
           $user = [
                'name' =>$this->auth->name,
                'img' => $this->auth->img
           ];
           $postUser->notify(new CommentNotification(
            $title,
            [ 
                'id' => $post->id,
                'post' => $post->post
            ], 
            $user,
            [
                'id' => $this->comment->id,
                'comment' => $this->comment->comment,
                'created_at' => $this->comment->created_at,
                'user' => $user
            ]));
        
    }
}
