<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CommentsSeeder extends BaseSeeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $posts = self::$posts;
        $users_count = self::$users_count;
        $users = self::$users;
        $comments = [];

        foreach ($posts as $post) {
            $post_id = $post['id'];
            $comment_per_post = rand(0,$users_count);
            for ($j=0; $j < $comment_per_post; $j++) {
                $date = now()->format('Y-m-d H:i:s');
                $comments[] = [
                    'content' => fake()->paragraph(),
                    'user_id' => $users[$j]['id'],
                    'post_id' => $post_id,
                    'created_at' => $date,
                    'updated_at' => $date
                ];
            }
        }

        DB::table('comments')->insert($comments);
    }
}
