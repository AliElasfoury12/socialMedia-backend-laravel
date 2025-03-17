<?php

use App\Http\Controllers\PostController;
use App\Models\User;
use Illuminate\Support\Benchmark;
use Illuminate\Support\Facades\Route;
use Carbon\Carbon;

Route::get('/', [PostController::class, 'index']);
