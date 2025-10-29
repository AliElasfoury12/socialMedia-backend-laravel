<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PostsImagesSeeder extends BaseSeeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $posts = self::$posts;
        $posts_count = self::$posts_count;

        $images_count = 0;
        $path = base_path('../pics/posts');
        $images = $this->get_images_from_path($path,$images_count);

        $posts_half_count = (int) $posts_count / 2 ;

        $posts_images = [];

        for ($i=$posts_half_count; $i < $posts_count; $i++) { 
            $image_per_post = rand(1,$images_count);
            $post_id = $posts[$i]['id'];
            for ($j=0; $j < $image_per_post; $j++) { 
                $image_url = $this->store_image('posts',$images[$j]);
                $date = now()->format('Y-m-d H:i:s');
                $posts_images[] = [
                    'post_id' => $post_id,
                    'img' => $image_url,
                    'created_at' => $date,
                    'updated_at' => $date
                ];
            }
        }

        DB::table('post_imgs')->insert($posts_images);
    }
}
