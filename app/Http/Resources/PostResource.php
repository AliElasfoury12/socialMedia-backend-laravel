<?php

namespace App\Http\Resources;

use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class PostResource extends JsonResource
{
    private function user($user) {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'img' => $user->img,
            'follows' => count($user->follows)
        ];
    }
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $shared_post = $this->sharedPost?->post;

        return [
            'id' => $this->id,
            'post' => Str::limit($this->post, 80),
            'post_imgs' => $this->postImgs,
            'created_at' => $this->created_at,
            'likes_count' =>$this->likes_count,
            'likes' => $this->likes ?? 0,
            'commentsCount' => $this->comments_count ?? 0,
            'user' => $this->user($this->user),
            'shared_post' =>  $this->sharedPost ? [
                'id' => $shared_post->id,
                'post' => Str::limit( $shared_post->post, 80),
                'post_imgs' =>  $shared_post->postImgs,
                'user' => $this->user($shared_post->user),
                'created_at' => $shared_post->created_at,
            ] : []
        ];
    }
}
