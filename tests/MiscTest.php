<?php

namespace OpenDominion\Tests;

use Laravel\BrowserKitTesting\DatabaseMigrations;

class MiscTest extends AbstractBrowserKitTestCase
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
