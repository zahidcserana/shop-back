<?php
use Illuminate\Support\Facades\Hash;

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| Here you may define all of your model factories. Model factories give
| you a convenient way to create models for testing and seeding your
| database. Just tell the factory how a default model should look.
|
*/


$factory->define(App\Models\PharmacyBranch::class, function (Faker\Generator $faker) {
    return [
        'branch_name'     => $faker->company,
        'branch_mobile'    => $faker->phoneNumber,
        'branch_full_address'    => $faker->address
    ];
});

