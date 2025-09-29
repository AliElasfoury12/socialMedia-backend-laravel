<?php

use App\Http\Controllers\CommentController;
use App\Http\Controllers\NotificationsController;
use App\Http\Controllers\PostController;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Repositories\PostRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Benchmark;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Carbon\Carbon;
use function React\Promise\all;

Route::get('/', function (Request $request) 
{
   // $notifcationscontoller = new NotificationsController;
   // $notifications = $notifcationscontoller->notificationsPost(417,0); 
   // echo "<pre>";
   // print_r([$notifications->content()]);
   // echo"</pre>";
   $user = User::where('id',99)->first();
    echo "<pre>";
    print_r([$user->toArray(), now()->addDays(7)]);
    echo"</pre>";

});
