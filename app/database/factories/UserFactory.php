<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use OpenDominion\Models\User;

class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = User::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'email' => $this->faker->unique()->safeEmail,
            'password' => bcrypt('secret'),
            'display_name' => $this->faker->unique()->name,
            'remember_token' => Str::random(10),
            'activated' => true,
            'activation_code' => Str::random(),
            'last_online' => null,
        ];
    }
}
