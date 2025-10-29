<?php

namespace Database\Seeders;

use DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LikesSeeder extends BaseSeeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $posts = self::$posts;
        $users_count = self::$users_count;
        $users = self::$users;

        $likes = [];
        foreach ($posts as $post) {
            $post_id = $post['id'];
            $like_per_post = rand(0,$users_count);
            for ($j=0; $j < $like_per_post; $j++) {
                $date = now()->format('Y-m-d H:i:s');
                $likes[] = [
                    'user_id' => $users[$j]['id'],
                    'post_id' => $post_id,
                    'created_at' => $date,
                    'updated_at' => $date
                ];
            }
        }

        DB::table('likes')->insert($likes);
    }
}
