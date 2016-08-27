<?php

namespace OpenDominion;

use Illuminate\Foundation\Application as LaravelApplication;

class Application extends LaravelApplication
{
    protected $appPath;

    public function __construct($basePath)
    {
        parent::__construct($basePath);

        $this->appPath = ($this->basePath() . DIRECTORY_SEPARATOR . 'app');
        $this->bindPathsInContainer();
    }

    public function bootstrapPath()
    {
        return ($this->appPath . DIRECTORY_SEPARATOR . 'bootstrap');
    }

    public function configPath()
    {
        return ($this->appPath . DIRECTORY_SEPARATOR . 'config');
    }

    public function databasePath()
    {
        return ($this->appPath . DIRECTORY_SEPARATOR . 'database');
    }

    public function langPath()
    {
        return ($this->appPath . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'lang');
    }

    public function storagePath()
    {
        return ($this->appPath . DIRECTORY_SEPARATOR . 'storage');
    }
}
