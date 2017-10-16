<?php

use Faker\Generator as Faker;

/** @var Illuminate\Database\Eloquent\Factory $factory */
$factory->define(\OpenDominion\Models\User::class, function (Faker $faker) {
    static $password;

    return [
        'email' => $faker->unique()->safeEmail,
        'password' => $password ?: $password = bcrypt('secret'),
        'display_name' => $faker->unique()->name,
        'remember_token' => str_random(10),
        'activated' => true,
        'activation_code' => str_random(60),
        'last_online' => null,
    ];
});
