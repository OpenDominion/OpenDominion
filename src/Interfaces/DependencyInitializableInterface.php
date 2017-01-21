<?php

namespace OpenDominion\Interfaces;

interface DependencyInitializableInterface
{
    /**
     * Initializes the class's dependencies.
     *
     * Used to circumvent the circular dependency problem.
     */
    public function initDependencies();
}
