<?php

namespace OpenDominion\Tests;

use Laravel\BrowserKitTesting\TestCase;
use Mockery;
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
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('honeypot.enabled', false);

//        Bus::fake();
//        Event::fake();
//        Mail::fake();
        Notification::fake();
//        Queue::fake();
    }

    /**
     * Clean up after each test to prevent memory leaks
     */
    protected function tearDown(): void
    {
        // Close all Mockery mocks to free memory
        Mockery::close();

        parent::tearDown();

        // Force garbage collection to reclaim memory
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
    }
}
