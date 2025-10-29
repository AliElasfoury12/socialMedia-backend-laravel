<?php

namespace Database\Seeders;

use App\Data\Image;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Str;
use Illuminate\Support\Facades\Storage;

class BaseSeeder extends Seeder
{
    private object $storage;
    public static  $posts = [];
    public static  $users = [];
    public static int $users_count = 40;
    public static int $posts_per_user = 10;
    public static int $posts_count = 0;


    public function __construct() {
        $this->storage = Storage::disk('public');
        self::$posts_count = self::$users_count * self::$posts_per_user; 
    }
    public function get_images_from_path (string $path, int &$images_count): array 
    {
        $images = scandir($path);
        $images_count = count($images);

        for ($i=0; $i <$images_count-2 ; $i++) { 
            $images[$i] = $images[$i+2];
        }

        unset($images[$images_count-1], $images[$images_count-2]);

        foreach ($images as &$image) {
            $image_path = "$path/$image";
            $image = new Image;
            $image->content = file_get_contents($image_path);
            $image->extension = pathinfo($image_path, PATHINFO_EXTENSION);
        }

        $images_count-=2;
        return $images;
    }

    public function store_image (string $path,Image $image): string 
    {
        $image_name = Str::random(32);
        $image_url = "$image_name.$image->extension";
        $this->storage->put("$path/$image_url", $image->content);
        return $image_url;
    }
}
