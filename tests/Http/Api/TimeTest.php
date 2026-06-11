<?php

namespace OpenDominion\Tests\Http\Api;

use OpenDominion\Tests\AbstractTestCase;

class TimeTest extends AbstractTestCase
{
    public function testTimeEndpointReturnsCurrentServerTime()
    {
        $response = $this->get('/api/v1/time');

        $response
            ->assertStatus(200)
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertJsonStructure(['t']);

        $this->assertMatchesRegularExpression('/^\d{2}:\d{2}:\d{2}$/', $response->json('t'));
    }
}
