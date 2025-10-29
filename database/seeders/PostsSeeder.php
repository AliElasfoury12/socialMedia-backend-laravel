<?php

namespace Database\Seeders;

use App\Models\Post;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PostsSeeder extends BaseSeeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $last_post_id = Post::max('id') ?? 0;
        $last_post_id++;
        $post_id = $last_post_id;

        $posts = [];

        for ($i=0; $i < self::$users_count; $i++) { 
            for ($j=0; $j < self::$posts_per_user; $j++) { 
                $date = now()->format('Y-m-d H:i:s'); 
                $post = [
                    'id' => $post_id,
                    'content' => fake()->paragraph(),
                    'user_id' => self::$users[$j]['id'],
                    'created_at' => $date,
                    'updated_at' => $date
                ];
                $post_id++;
                $posts[] = $post;
            }
        }
        
        Post::insert($posts);
        self::$posts = $posts;
    }
}
