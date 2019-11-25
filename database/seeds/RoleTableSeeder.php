<?php

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $standardRole              = new Role();
        $standardRole->name        = 'standard';
        $standardRole->description = 'A standard user';
        $standardRole->save();

        $adminRole              = new Role();
        $adminRole->name        = 'admin';
        $adminRole->description = 'Access to administration';
        $adminRole->save();
    }
}
