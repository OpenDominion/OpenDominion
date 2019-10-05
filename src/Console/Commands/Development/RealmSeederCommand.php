<?php

namespace OpenDominion\Console\Commands\Development;

use Illuminate\Console\Command;
use OpenDominion\Console\Commands\CommandInterface;
use OpenDominion\Factories\RealmFactory;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Race;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Round;
use OpenDominion\Models\User;
use RuntimeException;

class RealmSeederCommand extends Command implements CommandInterface
{
    /** @var string The name and signature of the console command. */
    protected $signature = 'dev:seed:realms
                             {--count=20 : Number of realms to fully populate with Faker dominion data}';

    /** @var string The console command description. */
    protected $description = 'Creates new realms and dominions for testing.';

    /**
     * {@inheritdoc}
     */
    public function handle(): void
    {
        $count = $this->option('count');

        if ($count < 0) {
            throw new RuntimeException('Option --count must be greater than zero.');
        }

        $round = Round::all()->last();
        if($round) {
            $realms = Realm::where('round_id', '=', $round->id)->get();
            foreach($realms as $realm) {
                $races = Race::where('alignment', '=', $realm->alignment)->get(['id'])->toArray();
                $dom_count = Dominion::where('realm_id', $realm->id)->count();
                $user = factory(User::class, $round->realm_size - $dom_count)->create()->each(function ($u) use ($round, $realm, $races) {
                    factory(Dominion::class)->create([
                        'user_id' => $u->id,
                        'round_id' => $round->id,
                        'realm_id' => $realm->id,
                        'race_id' => $races[array_rand($races)]['id'],
                    ]);
                });
            }
            for ($n = count($realms)+1; $n <= $count; $n++) {
                $alignment = rand(0, 1) ? 'good' : 'evil';
                $realm = app(RealmFactory::class)->create($round, $alignment);
                $races = Race::where('alignment', '=', $realm->alignment)->get(['id'])->toArray();
                $user = factory(User::class, $round->realm_size)->create()->each(function ($u) use ($round, $realm, $races) {
                    factory(Dominion::class)->create([
                        'user_id' => $u->id,
                        'round_id' => $round->id,
                        'realm_id' => $realm->id,
                        'race_id' => $races[array_rand($races)]['id'],
                    ]);
                });
            }
        } else {
            throw new RuntimeException('No rounds found, seed the development database first.');
        }
    }
}
