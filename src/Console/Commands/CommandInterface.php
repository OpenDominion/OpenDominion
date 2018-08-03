<?php

namespace OpenDominion\Console\Commands;

use Throwable;

interface CommandInterface
{
    /**
     * Execute the console command.
     *
     * @throws Throwable
     */
    public function handle(): void;
}
