<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FollowersSeeder extends BaseSeeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = self::$users;
        $followers = [];

        foreach ($users as $user) {
            $follower_per_user = rand(0,self::$users_count);
            for ($i=0; $i < $follower_per_user; $i++) { 
                $user_id = $user['id'];
                $follower_id = $users[$i]['id'];
                if($user_id == $follower_id) continue;
                $date = now()->format('Y-m-d H:i:s');
                $followers[] = [
                    'user_id' => $user_id,
                    'follower_id' => $follower_id,
                    'created_at' => $date,
                    'updated_at' => $date
                ];
            }
        }

        DB::table('followers')->insert($followers);
    }
}
