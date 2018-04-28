<?php

namespace OpenDominion;

use Illuminate\Foundation\Application as LaravelApplication;

class Application extends LaravelApplication
{
    /**
     * The application app path.
     *
     * @var string
     */
    protected $appPath;

    /**
     * Create a new OpenDominion application instance.
     *
     * @param string|null $basePath
     */
    public function __construct($basePath = null)
    {
        // We don't want to call the parent constructor, since we need to bind basePath manually, then set appPath, and
        // then call bindPathsInContainer(), exactly in that order.
        //
        // Parent constructor calls setBasePath(), which calls bindPathsInContainer() before we can set our appPath.
        // And we shouldn't set appPath before basePath, else our custom structure breaks.

        if ($basePath) {
            $this->basePath = rtrim($basePath, '\/');
        }

        $this->appPath = ($this->basePath() . DIRECTORY_SEPARATOR . 'app');

        $this->bindPathsInContainer();

        $this->registerBaseBindings();

        $this->registerBaseServiceProviders();

        $this->registerCoreContainerAliases();
    }

    /**
     * {@inheritdoc}
     */
    public function path($path = '')
    {
        return ($this->basePath . DIRECTORY_SEPARATOR . 'src' . $this->getSuffixPath($path));
    }

    /**
     * {@inheritdoc}
     */
    public function bootstrapPath($path = '')
    {
        return ($this->appPath . DIRECTORY_SEPARATOR . 'bootstrap' . $this->getSuffixPath($path));
    }

    /**
     * {@inheritdoc}
     */
    public function configPath($path = '')
    {
        return ($this->appPath . DIRECTORY_SEPARATOR . 'config' . $this->getSuffixPath($path));
    }

    /**
     * {@inheritdoc}
     */
    public function databasePath($path = '')
    {
        return ($this->appPath . DIRECTORY_SEPARATOR . 'database' . $this->getSuffixPath($path));
    }

    /**
     * {@inheritdoc}
     */
    public function langPath()
    {
        return ($this->appPath . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'lang');
    }

    /**
     * {@inheritdoc}
     */
    public function resourcePath($path = '')
    {
        return ($this->appPath . DIRECTORY_SEPARATOR . 'resources' . $this->getSuffixPath($path));
    }

    /**
     * Get path prefixed by directory separator if not empty.
     *
     * @param string $path
     * @return string
     */
    protected function getSuffixPath($path = '')
    {
        return ($path ? (DIRECTORY_SEPARATOR . $path) : '');
    }
}
