<?php

namespace App\Http\Resources;

use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;
use stdClass;

class PostResource extends JsonResource
{
   
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            ...$this->post($this),
            'likes_count' => $this->likes_count,
            'commentsCount' => $this->comments_count,
            'shared_post' => $this->post($this->sharedPost) ?: new stdClass
        ];
    }

    private function user(object $user): array 
    {
        $data = [
            'id' => $user->id,
            'name' => $user->name,
            'img' => $user->img,
        ];

        if($user->follows) $data['follows'] = count($user->follows);
        return $data;
    }

    private function post (object $post): array
    {
        if($post->count()) {
           if($post[0]) $post = $post[0];
            return [
                'id' => $post->id,
                'content' => Str::limit($post->content, 80),
                'post_imgs' => $post->postImgs,
                'created_at' => $post->created_at,
                'user' => $this->user($post->user),
            ];
        }else return [];
    }
}
