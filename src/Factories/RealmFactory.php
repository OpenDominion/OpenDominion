<?php

namespace OpenDominion\Factories;

use Atrox\Haikunator;
use DB;
use OpenDominion\Contracts\Council\ForumServiceContract;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Round;
use OpenDominion\Repositories\RealmRepository;

class RealmFactory
{
    /** @var RealmRepository */
    protected $realms;

    /**
     * RealmFactory constructor.
     *
     * @param RealmRepository $realms
     * @param ForumServiceContract $forum
     */
    public function __construct(RealmRepository $realms, ForumServiceContract $forum)
    {
        $this->realms = $realms;
        $this->forum = $forum;
    }

    /**
     * Creates and returns a new Realm in a Round based on alignment.
     *
     * @param Round $round
     * @param string $alignment
     *
     * @return Realm
     */
    public function create(Round $round, $alignment)
    {
        // todo: whitelist $alignment?
        // todo: repositories?
        $results = DB::table('realms')
            ->select(DB::raw('MAX(realms.number) AS max_realm_number'))
            ->where('round_id', $round->id)
            ->limit(1)
            ->get();

        if (empty($results)) {
            $number = 1;
        } else {
            $number = ((int)$results[0]->max_realm_number + 1);
        }

        $realmName = ucwords(Haikunator::haikunate([
            'tokenLength' => 0,
            'delimiter' => ' '
        ]));

        $realm = $this->realms->create([
            'round_id' => $round->id,
            'alignment' => $alignment,
            'number' => $number,
            'name' => $realmName,
        ]);

        // Create a forum for this realm.
        $this->forum->createForRealm($realm);

        return $realm;
    }
}
