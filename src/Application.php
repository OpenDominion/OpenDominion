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

    public function path($path = '')
    {
        return ($this->basePath . DIRECTORY_SEPARATOR . 'src' . $this->getSuffixPath($path));
    }

    public function bootstrapPath($path = '')
    {
        return ($this->appPath . DIRECTORY_SEPARATOR . 'bootstrap' . $this->getSuffixPath($path));
    }

    public function configPath($path = '')
    {
        return ($this->appPath . DIRECTORY_SEPARATOR . 'config' . $this->getSuffixPath($path));
    }

    public function databasePath($path = '')
    {
        return ($this->appPath . DIRECTORY_SEPARATOR . 'database' . $this->getSuffixPath($path));
    }

    public function langPath()
    {
        return ($this->appPath . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'lang');
    }

    public function storagePath()
    {
        return ($this->appPath . DIRECTORY_SEPARATOR . 'storage');
    }

    protected function getSuffixPath($path = '')
    {
        return ($path ? (DIRECTORY_SEPARATOR . $path) : '');
    }
}
