<?php

namespace OpenDominion\Tests\Traits;

use Hash;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use PDO;

trait CreatesApplication
{
    /**
     * Creates the application.
     *
     * @return Application
     */
    public function createApplication(): Application
    {
        $app = require __DIR__ . '/../../bootstrap/app.php';

        /**
         * Old test applications can retain PDO handles until garbage collection.
         * Persistent handles share a server session, so destroying an old handle
         * can roll back the current test's transaction on that same session.
         */
        $app->afterBootstrapping(LoadConfiguration::class, function (Application $app): void {
            foreach ($app['config']->get('database.connections', []) as $name => $connection) {
                if (in_array($connection['driver'] ?? null, ['mysql', 'mariadb'], true)) {
                    $options = $connection['options'] ?? [];
                    $options[PDO::ATTR_PERSISTENT] = false;
                    $app['config']->set("database.connections.{$name}.options", $options);
                }
            }
        });

        $app->make(Kernel::class)->bootstrap();

        Hash::driver('bcrypt')->setRounds(4);

        return $app;
    }
}
