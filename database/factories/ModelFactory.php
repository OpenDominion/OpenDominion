<?php

/** @var Illuminate\Database\Eloquent\Factory $factory */

$factory->define(\OpenDominion\Models\User::class, function (Faker\Generator $faker) {
    return [
        'email' => $faker->safeEmail,
        'password' => bcrypt(str_random(10)),
        'display_name' => $faker->name,
        'remember_token' => str_random(10),
        'activated' => $faker->boolean(90),
        'activation_code' => str_random(10),
    ];
});
