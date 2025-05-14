<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'content'
    ];

    protected $hidden = [
        'pivot',
        'user_id'
    ];

    public function postImgs () 
    {
        return $this->hasMany(PostImg::class)->select(['id', 'post_id', 'img']);
    }

    public function user () 
    {
        return $this->belongsTo(User::class)
        ->select(['id','name','img'])
        ->withCount(['follows AS isAuthFollows']);
    }

    public function likes () 
    {
        return $this->belongsToMany(User::class, 'likes')->withTimestamps();
    }

    public function isLiked () 
    {
        return $this->likes()
        ->select(['id'])
        ->where('user_id', auth()->id());
    }

    public function comments () 
    {
        return $this->hasMany(Comment::class);
    }

    public function sharedPost () 
    {
        return $this
        ->belongsToMany(Post::class, 'shared_posts', 'post_id', 'shared_post_id')
        ->select(['id','user_id','content','shared_posts.created_at'])
        ->with(['postImgs','user'])
        ->withTimestamps();
    }

}
