<?php

namespace App\Jobs;

use App\Http\Resources\UserResource;
use App\Models\Post;
use App\Models\User;
use App\Notifications\LikeNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendLikeNotifiction implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public $postId;
    public $auth;
    public function __construct($postId , $auth)
    {
        $this->postId = $postId;
        $this->auth = $auth;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $post = Post::find($this->postId);
        if($post->user_id != $this->auth->id) {
            $postUser = User::find($post->user_id);
            $tiltle = $this->auth->name . ' ' . 'Liked your Post';
            $postUser->notify(new LikeNotification(
                $tiltle,
                [ 
                        'id' => $post->id,
                        'post' => $post->post
                    ], 
                    [
                        'name' =>  $this->auth->name,
                        'img' =>  $this->auth->img
                    ]
            ));
        }
    }
}
