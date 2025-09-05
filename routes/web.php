<?php

use App\Http\Controllers\PostController;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Benchmark;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Carbon\Carbon;
use function React\Promise\all;

Route::get('/', function () {
    // $post = new PostController(); 
    // $result = $post->index();
    
   var_dump( response()->json([
        'message' => 'OTP Matches',
        'token' => 'OTP'
   ])->status());
    // echo '<pre>';
    //     print_r(json_decode($result->content()));
    // echo'</pre>';

});
