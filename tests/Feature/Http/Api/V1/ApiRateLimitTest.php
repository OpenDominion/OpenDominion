<?php

namespace OpenDominion\Tests\Feature\Http\Api\V1;

use OpenDominion\Tests\AbstractTestCase;

class ApiRateLimitTest extends AbstractTestCase
{
    public function testExceedingRateLimitReturnsApiErrorFormat(): void
    {
        $response = null;
        for ($i = 0; $i <= 60; $i++) {
            $response = $this->getJson('/api/v1/rounds');
            if ($response->status() === 429) {
                break;
            }
        }

        $response
            ->assertStatus(429)
            ->assertHeader('Retry-After')
            ->assertExactJson([
                'error' => 'rate_limited',
                'message' => 'Too many requests. Retry after the number of seconds in the Retry-After header.',
            ]);
    }
}
