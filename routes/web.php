<?php

use App\Http\Controllers\CommentController;
use App\Http\Controllers\NotificationsController;
use App\Http\Controllers\PostController;
use App\JWT_Token\JWT_Token;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Repositories\PostRepository;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Benchmark;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Carbon\Carbon;
use function React\Promise\all;

Route::get('/', function (Request $request) 
{
    // $db_seeder = new DatabaseSeeder();
    // $db_seeder->run();

// users_seeder
    // $last_user_id = User::max('id') ?? 0;
    // $last_user_id++;

    // $users = User::factory(10)->make()->toArray();

    // $user_id = $last_user_id;

    // foreach ($users as &$user) {
    //     $date = now()->format('Y-m-d H:i:s');
    //     $user['email_verified_at'] = $date;
    //     $user['created_at'] = $date;
    //     $user['updated_at'] = $date;
    //     $user['id'] = $user_id;
    //     $user_id++;
    // }
    // User::insert($users);

//posts_seeder
//     $posts = [];
//     for ($i=0; $i < 10; $i++) { 
//         for ($j=0; $j < 10 ; $j++) { 
//             $user_id = $users[$j]['id']; 
//             $post = Post::factory(1)->make(['user_id' => $user_id])->toArray()[0];
//             $post['user_id'] = $user_id;
//             $posts[] = $post;
//         }
//     }

   
//   Post::insert($posts);

    $postsRepository = new PostRepository;
    $postController = new PostController;
    $posts = $postsRepository->getPosts(192,10,$request->cursor);
    $posts = $postController->formatResponse($posts);

    $a = new stdClass;
    $a->b = new stdClass;
    $a->b->c = null;
    $a->b->c ? print 'ali' : print '2' ;

     echo "<pre>";
     print_r($posts);
     echo"</pre>";
});
