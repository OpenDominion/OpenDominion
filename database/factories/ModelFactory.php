<?php

/** @var Illuminate\Database\Eloquent\Factory $factory */

$factory->define(\OpenDominion\Models\User::class, function (Faker\Generator $faker) {
    return [
        'email' => $faker->safeEmail,
        'password' => bcrypt(str_random()),
        'display_name' => $faker->name,
        'remember_token' => str_random(),
        'activated' => true,
        'activation_code' => str_random(),
    ];
});
