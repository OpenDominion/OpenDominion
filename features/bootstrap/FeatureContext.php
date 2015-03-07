<?php

use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\MinkExtension\Context\MinkContext;
use Laracasts\Behat\Context\App;
use Laracasts\Behat\Context\KernelAwareContext;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\User;
use OpenDominion\Repositories\DominionRepository;
use OpenDominion\Repositories\UserRepository;
use PHPUnit_Framework_Assert as PHPUnit;

/**
 * Defines application features from the specific context.
 */
class FeatureContext extends MinkContext implements Context, KernelAwareContext, SnippetAcceptingContext
{
    use App, PrepareTestEnvironment;

    /**
     * @var DominionRepository
     */
    public $dominions;

    /**
     * @var UserRepository
     */
    public $users;

    /**
     * Initializes context.
     */
    public function __construct()
    {
        $this->dominions = new DominionRepository(new Dominion);
        $this->users = new UserRepository(new User);
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

    /**
     * @Given /^user with email "([^"]*)" should exist$/
     * @param  string $email
     */
    public function userWithEmailShouldExist($email)
    {
        PHPUnit::assertTrue($this->users->doesUserWithEmailExist($email), 'User does not exist');
    }

    /**
     * @Given /^user with email "([^"]*)" should not exist$/
     * @param  string $email
     */
    public function userWithEmailShouldNotExist($email)
    {
        PHPUnit::assertFalse($this->users->doesUserWithEmailExist($email), 'User exists');
    }

    /**
     * @Given /^dominion with name "([^"]*)" should exist$/
     */
    public function dominionWithNameShouldExist($name)
    {
        PHPUnit::assertTrue($this->dominions->doesDominionWithEmailExist($name), 'Dominion does not exist');
    }

    /**
     * @Given /^dominion with name "([^"]*)" should not exist$/
     */
    public function dominionWithNameShouldNotExist($name)
    {
        PHPUnit::assertFalse($this->dominions->doesDominionWithEmailExist($name), 'Dominion exists');
    }
}
