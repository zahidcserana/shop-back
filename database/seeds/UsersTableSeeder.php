<?php

use Illuminate\Database\Seeder;
use App\Models\User;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $sadmin =  User::factory()->create([
            'id' => 1,
            'name' => 'AnalyticalJ',
            'email' => 'admin@analyticalj.com',
            'password' => Hash::make('aj$21'),
            'type' => User::ROLE_OWNER,
            'is_admin' => true
        ]);

        $admin =  User::factory()->create([
            'id' => 2,
            'name' => 'Admin',
            'email' => 'admin@admin.com',
            'password' => Hash::make('secret'),
            'type' => User::ROLE_ADMINISTRATOR,
        ]);

        $salesman = User::factory()->create([
            'id' => 3,
            'name' => 'Salesman',
            'email' => 'salesman@shop.com',
            'type' => User::ROLE_SALESMAN,
            'password' => Hash::make('secret'),
        ]);
    }
}