<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'post',
        'img'
    ];

    protected $hidden = [
        'user_id',
        'updated_at',
        'pivot'
    ];

    public function postImgs () 
    {
        return $this->hasMany(PostImg::class);
    }

    public function user () 
    {
        return $this->belongsTo(User::class)->select(['id','name']);
    }

    public function likes () 
    {
        return $this->belongsToMany(User::class, 'likes');
    }

    public function isLiked () 
    {
        return $this->likes()->where('user_id', auth()->id());
    }

    public function comments () 
    {
        return $this->hasMany(Comment::class);
    }

    public function sharedPost () 
    {
        return $this->hasOne(SharedPost::class);
    }

}
