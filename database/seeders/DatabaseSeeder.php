<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        function comment_seeder ($user, $post)
        {
            Comment::factory(2)->create(['user_id' => $user->id, 'post_id' => $post->id]);
        };

        $post_seeder = function($user)  
        {
            Post::factory(10)->create(['user_id' => $user->id])
            ->each(fn ($post) => comment_seeder($user, $post));
        };

        $users = User::factory(10)->create();

        
    }
}
