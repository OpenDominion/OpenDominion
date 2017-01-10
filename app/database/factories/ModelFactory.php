<?php

/** @var Illuminate\Database\Eloquent\Factory $factory */

use OpenDominion\Models\Dominion;
use OpenDominion\Models\User;

$factory->define(Dominion::class, function (Faker\Generator $faker) {
    // todo
    return [
        'name' => $faker->lastName,
        'prestige' => 0,
        'peasants' => 0,
        'peasants_last_hour' => null,
        'draft_rate' => 10,
        'morale' => 100,
        'resource_platinum' => 0,
        'resource_food' => 0,
        'resource_lumber' => 0,
        'military_draftees' => 0,
        'land_plain' => 0,
        'land_forest' => 0,
        'land_mountain' => 0,
        'land_hill' => 0,
        'land_swamp' => 0,
        'land_water' => 0,
        'land_cavern' => 0,
        'building_home' => 0,
        'building_alchemy' => 0,
        'building_farm' => 0,
        'building_lumberyard' => 0,
    ];
});

$factory->define(User::class, function (Faker\Generator $faker) {
    return [
        'email' => $faker->safeEmail,
        'password' => bcrypt(str_random()),
        'display_name' => $faker->name,
        'activated' => true,
        'activation_code' => str_random(),
    ];
});
