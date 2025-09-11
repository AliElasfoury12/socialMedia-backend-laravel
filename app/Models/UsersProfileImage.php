<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UsersProfileImage extends Model
{
    protected $fillable = [
        'user_id',
        'url'
    ];

    protected $hidden = [
        'id',
        'user_id'
    ];
}
