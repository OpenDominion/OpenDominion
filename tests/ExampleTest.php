<?php

namespace OpenDominion\Tests;

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use OpenDominion\Models\User;

class ExampleTest extends BaseTestCase
{
    use DatabaseMigrations;

    public function testBasicExample()
    {
        $this->visit('/')
            ->see('Home content');
    }

    public function testUserCanLogin()
    {
        $password = str_random();

        $user = factory(User::class)->create([
            'password' => bcrypt($password),
            'activated' => true,
        ]);

        $this->visit('/auth/login')
            ->see('Login')
            ->type($user->email, 'email')
            ->type($password, 'password')
            ->press('Login')
            ->seePageIs('/status')
            ->see('temp status page');
    }
}
