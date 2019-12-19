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
        Role::query()->firstorCreate([
            'name' => 'standard',
        ], [
            'description' => 'A standard user',
        ]);

        Role::query()->firstorCreate([
            'name' => 'admin',
        ], [
            'description' => 'Access to administration',
        ]);
    }
}
