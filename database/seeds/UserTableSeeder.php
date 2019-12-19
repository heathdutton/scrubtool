<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        /** @var User $admin */
        $admin     = User::query()->firstOrCreate([
            'id' => 1,
        ], [
            'name'     => 'Admin',
            'email'    => 'admin@scrubtool.com',
            'password' => bcrypt('secret'),
        ]);
        $roleAdmin = Role::query()->where('name', 'admin')->first();
        $admin->roles()->attach($roleAdmin);
    }
}
