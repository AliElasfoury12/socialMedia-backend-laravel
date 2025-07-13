<?php

use App\Http\Controllers\CommentController;
use App\Http\Controllers\ImagesController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\NotificationsController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\UserController;
use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Models\ProfilePic;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;


Route::group(['middleware'=>['auth:sanctum','throttle:api']],function () 
{
    Route::apiResource('posts.comments', CommentController::class)
    ->except(['show']);

    Route::apiResource('posts', PostController::class);

    Route::controller(PostController::class)->group(function (): void 
    {
        Route::get('search-posts/{search}', 'searchPosts');
        Route::post('share-post','sharePost');
    });

    Route::controller(ImagesController::class)->group(function (): void 
    {
        Route::delete('delete-images/{post}', 'deletePostImages');
        Route::post('change-profile-picture/{user}','changrProfilePic');
    });

    Route::get('like/{post}',[LikeController::class, 'like']);

    Route::apiResource('users', UserController::class)
    ->except(['store','destroy']);

    Route::controller(UserController::class)->group(function () {
        Route::get('search-users/{search}','searchUsers');
        Route::get('user-posts/{id}','userPosts');
        Route::post('delete-account/{user}', 'destroy');
        Route::get('follow/{user}','follow');
    });

    Route::controller(NotificationsController::class)->group(function () {
        Route::get('notifications','index');
        Route::get('notifications/mark-all-as-read','markAllAsRead');
        Route::get('notifications/mark-as-read/{id}','markAsRead');
        Route::get('notifications/seen', 'seen');
        Route::get('notifications/post/{post}/comment/{commentId}','notificationsPost');
    });  
});


Route::get('test', function () {
    //return $request->user()->id;
    //return auth()->id();
    //return request()->user()->id;
    $posts = Post::latest()->paginate(3);
    $posts->load(['sharedPost']);
   // return response()->json(PostResource::collection($posts));
   return PostResource::collection($posts);
});
