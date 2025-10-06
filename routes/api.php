<?php

use App\Http\Controllers\CommentController;
use App\Http\Controllers\ImagesController;
use App\Http\Controllers\NotificationsController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UsersProfileImageController;
use Illuminate\Support\Facades\Route;

$post_routes = function ()   
{
    Route::controller(PostController::class)->group(function (): void 
    {
        Route::post('posts/share/{post}','sharePost');
        Route::get('posts/like/{post}', 'like');
        Route::get('posts/search/{search}', 'searchPosts');
    });

    Route::apiResource('posts', PostController::class);
};

$comments_routes = function () 
{
    Route::apiResource('posts.comments', CommentController::class)->only(['index', 'store']);
    Route::apiResource('comments', CommentController::class)->only(['update', 'destroy']);
};

$images_routes = function () 
{
    Route::delete('delete-images/{post}', [ImagesController::class,'deletePostImages']);
    Route::apiResource('user-profile-image',UsersProfileImageController::class);
};

$users_routes = function () 
{
    Route::controller(UserController::class)->group(function () 
    {
        Route::get('users/{user}', 'show');
        Route::put('users', 'update');
        Route::delete('users', 'destroy');
        Route::get('search-users/{search}','searchUsers');
        Route::get('user-posts/{id}','userPosts');
        Route::get('follow/{user}','follow');
    });

};

$notifications_routes = function () 
{
    Route::controller(NotificationsController::class)->group(function () {
        Route::get('notifications','index');
        Route::get('notifications-count','getNotificationsCount');
        Route::get('notifications/mark-all-as-read','markAllAsRead');
        Route::get('notifications/mark-as-read/{id}','markAsRead');
        Route::get('notifications/seen', 'seen');
        Route::get('notifications/post/{post}/comment/{commentId}','notificationsPost');
    });  
};

Route::group(['middleware'=>['throttle:api','jwt_auth']],function () 
    use ($post_routes, $comments_routes, $images_routes, $users_routes, $notifications_routes)
{
    $post_routes();
    $comments_routes();
    $images_routes();
    $users_routes();
    $notifications_routes();
});

