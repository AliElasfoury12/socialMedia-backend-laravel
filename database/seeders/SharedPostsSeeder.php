<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SharedPostsSeeder extends BaseSeeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $posts = self::$posts;
        $posts_count = self::$posts_count;

        $shared_posts = [];
        $posts_half_count = (int) $posts_count / 2 ;

        for ($i=0; $i < $posts_half_count; $i++) {
            $date = now()->format('Y-m-d H:i:s');
            $shared_posts[] = [
                'post_id' =>  $posts[$i]['id'],
                'shared_post_id' => $posts[$posts_count-1-$i]['id'],
                'created_at' => $date,
                'updated_at' => $date
            ];
        }

        DB::table('shared_posts')->insert($shared_posts);
    }
}
