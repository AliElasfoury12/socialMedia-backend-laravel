<?php

namespace Database\Seeders;

use App\Models\UsersProfileImage;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserProfileImagesSeeder extends BaseSeeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = self::$users;
        $images_count = 0;
        $path = base_path('../pics/profile');
        $images = $this->get_images_from_path($path,$images_count);

        $last_image_id = UsersProfileImage::max('id') ?? 0;
        $last_image_id++;
        $image_id = $last_image_id;

        $users_ids_string = '';
        $profile_images = [];
        $current_profile_images_ids = [];

        foreach ($users as $user) {
            $user_id = $user['id'];
            $users_ids_string .= "$user_id,";
            $images_per_user = rand(0,$images_count);

            for ($i=0; $i < $images_per_user ; $i++) { 
                $image_url = $this->store_image('profile', $images[$i]);

                $profile_images[] = [
                    'id' => $image_id,
                    'user_id' => $user_id,
                    'url' => $image_url,
                    'created_at' => now()->format('Y-m-d H:i:s')
                ];

                if($i == $images_per_user-1){
                    $current_profile_images_ids[] = ['user_id' => $user_id, 'image_id' => $image_id];
                }
                $image_id++;
            }
        }

        $users_ids_string = rtrim($users_ids_string,',');

        UsersProfileImage::insert($profile_images);
        $this->update_users_with_images($current_profile_images_ids, $users_ids_string);
    }

    private function update_users_with_images (array $current_profile_images_ids, string $users_ids_string) 
    {
        $cases = '';

        foreach ($current_profile_images_ids as $profile_image) {
            $image_id = $profile_image['image_id'];
            $user_id = $profile_image['user_id'];
            $cases.= " WHEN $user_id THEN $image_id";
        }


        DB::statement("UPDATE users 
            SET profile_image_id = CASE id
                $cases
            END 
            WHERE id IN ($users_ids_string)
        ");
    }
}
