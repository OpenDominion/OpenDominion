<?php

namespace OpenDominion\Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\TestCase;
use OpenDominion\Models\User;

abstract class BaseTestCase extends TestCase
{
    /**
     * The base URL to use while testing the application.
     *
     * @var string
     */
    protected $baseUrl = 'http://localhost';

    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $app = require __DIR__ . '/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    /**
     * Creates a user for testing purposes.
     *
     * @param string|null $password
     * @param array $attributes
     * @return User
     */
    protected function createUser($password = null, array $attributes = [])
    {
        if ($password !== null) {
            $attributes['password'] = bcrypt($password);
        }

        $user = factory(User::class)->create($attributes);
        return $user;
    }

    /**
     * Creates and impersonates a user for testing purposes.
     *
     * @param string|null $password
     * @return User
     */
    protected function createAndImpersonateUser($password = null, array $attributes = [])
    {
        $user = $this->createUser($password, $attributes);
        $this->be($user);
        return $user;
    }
}
