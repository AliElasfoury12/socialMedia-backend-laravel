<?php

use App\Http\Controllers\CommentController;
use App\Http\Controllers\NotificationsController;
use App\Http\Controllers\PostController;
use App\JWT_Token\JWT_Token;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Repositories\PostRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Benchmark;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Carbon\Carbon;
use function React\Promise\all;

Route::get('/', function (Request $request) 
{
    $user2 = new User([
        'id' => 99,
        'name' => 'ali2',
        'email' => 'ali2@g.c',
        'password' => '$2y$12$byGlRLUThjVrhmoAsaqiZeWyB73z9nzYbwDuqnjFua1HIe7X2KT4C'
    ]);

    $user2->exists = true;
    $user2->id = 99;
    $user2->syncOriginal();

    $user2->update(['name' => 'ali23']);
   


    echo "<pre>";
    print_r([$user2]);
    echo"</pre>";
});
