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
   try {
    $x = 10;
    throw new Exception('ali');
   } catch (\Throwable $th) {
    echo $th->getMessage();
   }
    echo "<pre>";
    print_r([$x ]);
    echo"</pre>";
});
