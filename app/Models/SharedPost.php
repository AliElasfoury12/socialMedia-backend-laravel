<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SharedPost extends Model
{
    protected $fillable = [
        'user_id',
        'post_id',
        'shared_post_id'
    ];
    
    public function post () {
        return $this->belongsTo(Post::class, 'shared_post_id')
        ->with(['user:id,name,img', 'postImgs']);
    }
}
