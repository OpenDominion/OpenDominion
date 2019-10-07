<?php

namespace OpenDominion\Tests;

use Illuminate\Foundation\Testing\DatabaseTransactions;

class MiscTest extends AbstractBrowserKitTestCase
{
    use DatabaseTransactions;

    public function testHomepage()
    {
        $this->visit('/')
            ->see('Welcome to OpenDominion');
    }

    public function testUserLastOnlineGetsUpdated()
    {
        // todo: move this somewhere
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
