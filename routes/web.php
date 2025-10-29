<?php

use App\Http\Controllers\CommentController;
use App\Http\Controllers\NotificationsController;
use App\Http\Controllers\PostController;
use App\JWT_Token\JWT_Token;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Models\UsersProfileImage;
use App\Repositories\PostRepository;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\UsersSeeder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Benchmark;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use function React\Promise\all;

Route::get('/', function (Request $request) 
{

	$images = UsersProfileImage::select(['url'])->where('user_id', 91)->get();
	
	echo '<pre>';
	print_r($images->toArray()); 
	echo'</pre>';
});
