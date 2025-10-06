<?php

namespace App\Models;


// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'pivot',
        'profile_image_id'
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
   
    public function followings() {
        return $this->belongsToMany(User::class,'followers','follower_id', 'user_id')->withTimestamps();;
    }

    public function followers() {
        return $this->belongsToMany(User::class,'followers','user_id', 'follower_id')->withTimestamps();;
    }

    public function isAuthUserFollows ()
    {
       return $this->followers()
       ->select('users.id')
       ->where('follower_id', auth()->id())->limit(1);
    }

    public function profilePic ()  
    {
        return $this->hasOne(UsersProfileImage::class, 'id','profile_image_id')
        ->select(['id', 'url']);
    }

    public function receivesBroadcastNotificationsOn(): string
    {
        return 'notifications.'.$this->id;
    }

}
