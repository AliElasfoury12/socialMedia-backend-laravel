<?php

use App\Http\Controllers\PostController;
use App\Models\User;
use Illuminate\Support\Benchmark;
use Illuminate\Support\Facades\Route;
use Carbon\Carbon;

Route::get('/', function () {
   // $posts = new PostController;
   
  // $result = Benchmark::measure(fn() =>  $posts->index());

  // dd($result);
/*
    function setTimeOut ($fun, $time) {
       sleep($time);
       return $fun;
    }
*/
   // setTimeOut(fn() => dd('hi'),3);
    return view('welcome');
});
