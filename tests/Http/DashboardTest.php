<?php

namespace OpenDominion\Tests\Http;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

class DashboardTest extends AbstractBrowserKitTestCase
{
    use DatabaseTransactions;

    public function testDashboardPage()
    {
        $this->visitRoute('dashboard')
            ->seeRouteIs('auth.login');

        $this->createAndImpersonateUser();

        $this->visitRoute('dashboard')
            ->seeStatusCode(200);
    }
}
