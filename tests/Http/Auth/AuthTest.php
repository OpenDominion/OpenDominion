<?php

namespace OpenDominion\Tests\Http\Auth;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use OpenDominion\Tests\AbstractHttpTestCase;

class AuthTest extends AbstractHttpTestCase
{
    use DatabaseMigrations;

    public function testGuestCantAccessProtectedPages()
    {
        $this->get('/dashboard')
            ->assertRedirect('/auth/login');

        // todo: expand?
    }

    public function testAuthenticatedUserCantAccessLoginAndRegisterPages()
    {
        $this->createAndImpersonateUser();

        $this->get('/auth/login')
            ->assertRedirect('/');

        $this->get('/auth/register')
            ->assertRedirect('/');
    }
}
