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
        $roleAdmin       = Role::where('name', 'admin')->first();
        $admin           = new User();
        $admin->name     = 'Admin';
        $admin->email    = 'admin@scrubtool.com';
        $admin->password = bcrypt('secret');
        $admin->save();
        $admin->roles()->attach($roleAdmin);
    }
}
