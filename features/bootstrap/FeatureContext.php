<?php

use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\MinkExtension\Context\MinkContext;
use Laracasts\Behat\Context\App;
use Laracasts\Behat\Context\KernelAwareContext;
use OpenDominion\Models\User;
use PHPUnit_Framework_Assert as PHPUnit;

//require 'PHPUnit/Autoload.php';
//require 'PHPUnit/Framework/Assert/Functions.php';

/**
 * Defines application features from the specific context.
 */
class FeatureContext extends MinkContext implements Context, KernelAwareContext, SnippetAcceptingContext
{
    use App, PrepareTestEnvironment;

    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     */
    public function __construct()
    {
    }

    /**
     * @Given /^I am not logged in$/
     */
    public function iAmNotLoggedIn()
    {
        $this->app['auth']->logout();
    }

    /**
     * @Given /^I am logged in$/
     */
    public function iAmLoggedIn()
    {
        $user = User::findOrFail(1);
        $this->app['auth']->login($user);
    }

    /**
     * @Then /^I should be logged in$/
     */
    public function iShouldBeLoggedIn()
    {
        PHPUnit::assertTrue($this->app['auth']->check(), 'I am not logged in');
    }

    /**
     * @Then /^I should not be logged in$/
     */
    public function iShouldNotBeLoggedIn()
    {
        PHPUnit::assertTrue($this->app['auth']->guest(), 'I am logged in');
    }
}
