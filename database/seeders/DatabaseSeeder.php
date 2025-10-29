<?php

namespace Database\Seeders;

use App\Data\Image;
use App\Models\Post;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\UsersProfileImage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Str;

class DatabaseSeeder extends Seeder
{
    private object $storage;

    public function __construct() {
        $this->storage = Storage::disk('public');
    }

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $start = microtime(true);

        $this->call([
            UsersSeeder::class,
            FollowersSeeder::class,
            UserProfileImagesSeeder::class,
            PostsSeeder::class,
            LikesSeeder::class,
            CommentsSeeder::class,
            SharedPostsSeeder::class,
            PostsImagesSeeder::class
        ]);

        $end = microtime(true);
        $execution_time = $end - $start;
        echo "Time: $execution_time \n";
    }
}
