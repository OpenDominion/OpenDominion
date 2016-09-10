<?php

namespace OpenDominion\Tests;

use Illuminate\Foundation\Testing\DatabaseMigrations;

class MiscTest extends BaseTestCase
{
    use DatabaseMigrations;

    public function testHomepage()
    {
        $this->visit('/')
            ->see('Welcome to OpenDominion');
    }

    public function testUserLastOnlineGetsUpdated()
    {
        $user = $this->createAndImpersonateUser();

        $this
            ->seeInDatabase('users', [
                'id' => $user->id,
                'last_online' => null,
            ])
            ->visit('/')
            ->dontSeeInDatabase('users', [
                'id' => $user->id,
                'last_online' => null,
            ]);
    }
}
