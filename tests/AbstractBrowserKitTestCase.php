<?php

namespace OpenDominion\Tests;

use Laravel\BrowserKitTesting\TestCase;
use Notification;
use OpenDominion\Tests\Traits\CreatesApplication;
use OpenDominion\Tests\Traits\CreatesData;

abstract class AbstractBrowserKitTestCase extends TestCase
{
    use CreatesApplication;
    use CreatesData;

    /**
     * The base URL of the application.
     *
     * @var string
     */
    protected $baseUrl = 'http://localhost';

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        config()->set('honeypot.enabled', false);

//        Bus::fake();
//        Event::fake();
//        Mail::fake();
        Notification::fake();
//        Queue::fake();
    }
}
