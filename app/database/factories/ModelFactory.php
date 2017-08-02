<?php

use OpenDominion\Models\Dominion;
use OpenDominion\Models\User;

/** @var Illuminate\Database\Eloquent\Factory $factory */
$factory->define(Dominion::class, function (Faker\Generator $faker) {
    return [
        'name' => $faker->unique()->name,
        'networth' => 0,
        'prestige' => 250,
        'peasants' => 1300,
        'peasants_last_hour' => 0,
        'draft_rate' => 10,
        'morale' => 100,
        'spy_strength' => 100,
        'wizard_strength' => 100,
        'resource_platinum' => 100000,
        'resource_food' => 15000,
        'resource_lumber' => 15000,
        'resource_mana' => 0,
        'resource_ore' => 0,
        'resource_gems' => 10000,
        'resource_tech' => 0,
        'resource_boats' => 0,
        'improvement_science' => 0,
        'improvement_keep' => 0,
        'improvement_towers' => 0,
        'improvement_forges' => 0,
        'improvement_walls' => 0,
        'improvement_irrigation' => 0,
        'military_draftees' => 100,
        'military_unit1' => 0,
        'military_unit2' => 150,
        'military_unit3' => 0,
        'military_unit4' => 0,
        'military_spies' => 25,
        'military_wizards' => 25,
        'military_archmages' => 25,
        'land_plain' => 110,
        'land_mountain' => 20,
        'land_swamp' => 20,
        'land_cavern' => 20,
        'land_forest' => 40,
        'land_hill' => 20,
        'land_water' => 20,
        'building_home' => 10,
        'building_alchemy' => 20,
        'building_farm' => 30,
        'building_smithy' => 0,
        'building_masonry' => 0,
        'building_ore_mine' => 0,
        'building_gryphon_nest' => 0,
        'building_tower' => 0,
        'building_wizard_guild' => 0,
        'building_temple' => 0,
        'building_diamond_mine' => 0,
        'building_school' => 0,
        'building_lumberyard' => 20,
        'building_forest_haven' => 0,
        'building_factory' => 0,
        'building_guard_tower' => 0,
        'building_shrine' => 0,
        'building_barracks' => 0,
        'building_dock' => 0,
    ];
});

$factory->define(User::class, function (Faker\Generator $faker) {
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
