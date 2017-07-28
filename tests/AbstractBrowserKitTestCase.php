<?php

namespace OpenDominion\Tests;

use Carbon\Carbon;
use Laravel\BrowserKitTesting\TestCase;
use Mail;
use OpenDominion\Factories\DominionFactory;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Race;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Round;
use OpenDominion\Models\User;
use OpenDominion\Services\Dominion\SelectorService;

abstract class AbstractBrowserKitTestCase extends TestCase
{
    use CreatesApplication;

    /**
     * The base URL of the application.
     *
     * @var string
     */
    protected $baseUrl = 'http://localhost';

    protected function setUp()
    {
        parent::setUp();

//        Bus::fake();
//        Event::fake();
        Mail::fake();
//        Notification::fake();
//        Queue::fake();
    }

    // todo: move below to a trait so we can also include this in the abstractdusktestcase

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
     * @param array $attributes
     * @return User
     */
    protected function createAndImpersonateUser($password = null, array $attributes = [])
    {
        $user = $this->createUser($password, $attributes);
        $this->be($user);
        return $user;
    }

    /**
     * Creates a round for testing purposes.
     *
     * @param string $startDate Carbon-parsable string
     * @param string $endDate Carbon-parsable string
     * @return Round
     */
    protected function createRound($startDate = 'today', $endDate = '+50 days')
    {
        $round = Round::create([
            'round_league_id' => 1,
            'number' => 1,
            'name' => 'Testing Round',
            'start_date' => new Carbon($startDate . ' midnight'),
            'end_date' => new Carbon($endDate . ' midnight'),
        ]);

        return $round;
    }

    /**
     * @param Round $round
     * @param string $alignment 'good' or 'evil'
     * @return Realm
     */
    protected function createRealm(Round $round, $alignment = 'good')
    {
        $realm = Realm::create([
            'round_id' => $round->id,
            'alignment' => $alignment,
            'number' => 1,
            'name' => 'Testing Realm',
        ]);

        return $realm;
    }

    /**
     * @param User $user
     * @param Round $round
     * @param Race $race
     * @return Dominion
     */
    protected function createDominion(User $user, Round $round, Race $race = null)
    {
        $dominionFactory = $this->app->make(DominionFactory::class);

        $dominion = $dominionFactory->create(
            $user,
            $round,
            ($race ?: Race::firstOrFail()),
            'random',
            str_random()
        );

        return $dominion;
    }

    /**
     * @param Dominion $dominion
     * @return Dominion
     */
    protected function selectDominion(Dominion $dominion)
    {
        $dominionSelectorService = app(SelectorService::class);

        $dominionSelectorService->selectUserDominion($dominion);

        return $dominion;
    }

    /**
     * @param User $user
     * @param Round $round
     * @param Race $race
     * @return Dominion
     */
    protected function createAndSelectDominion(User $user, Round $round, Race $race = null)
    {
        $dominion = $this->createDominion($user, $round, $race);
        return $this->selectDominion($dominion);
    }
}
