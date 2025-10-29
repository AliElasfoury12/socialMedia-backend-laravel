<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UsersSeeder extends BaseSeeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $last_user_id = User::max('id') ?? 0;
        $last_user_id++;

        $users = User::factory(self::$users_count)->make()->toArray();

        $user_id = $last_user_id;

        foreach ($users as &$user) {
            $date = now()->format('Y-m-d H:i:s');
            $user['email_verified_at'] = $date;
            $user['created_at'] = $date;
            $user['updated_at'] = $date;
            $user['id'] = $user_id;
            $user_id++;
        }

        User::insert($users);

        self::$users = $users;
    }
}
