<?php

use App\Http\Controllers\CommentController;
use App\Http\Controllers\ImagesController;
use App\Http\Controllers\NotificationsController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UsersProfileImageController;
use Illuminate\Support\Facades\Route;


Route::group(['middleware'=>['auth:sanctum','throttle:api']],function (): void 
{
    //posts

    Route::apiResource('posts', PostController::class);

    Route::controller(PostController::class)->group(function (): void 
    {
        Route::post('posts/{post}/share','sharePost');
        Route::get('posts/{post}/like', 'like');
        Route::get('search-posts/{search}', 'searchPosts');
    });

    //comments

    Route::apiResource('posts.comments', CommentController::class)->only(['index', 'store']);
    Route::apiResource('comments', CommentController::class)->only(['update', 'destroy']);

    //images
    Route::delete('delete-images/{post}', [ImagesController::class,'deletePostImages']);
    Route::apiResource('user-profile-image',UsersProfileImageController::class);

    //users
    Route::apiResource('users', UserController::class)
    ->except(['store','destroy']);

    Route::controller(UserController::class)->group(function () {
        Route::get('search-users/{search}','searchUsers');
        Route::get('user-posts/{id}','userPosts');
        Route::post('delete-account/{user}', 'destroy');
        Route::get('follow/{user}','follow');
    });

    //notifications
    Route::controller(NotificationsController::class)->group(function () {
        Route::get('notifications','index');
        Route::get('notifications/mark-all-as-read','markAllAsRead');
        Route::get('notifications/mark-as-read/{id}','markAsRead');
        Route::get('notifications/seen', 'seen');
        Route::get('notifications/post/{post}/comment/{commentId}','notificationsPost');
    });  
});

