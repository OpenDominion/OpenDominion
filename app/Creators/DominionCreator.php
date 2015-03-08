<?php namespace OpenDominion\Creators;

use OpenDominion\Models\Dominion;
use OpenDominion\Models\User;

class DominionCreator
{
    /**
     * @var Dominion
     */
    protected $dominion;

    public function __construct(Dominion $dominion)
    {
        $this->dominion = $dominion;
    }

    /**
     * @param  User   $user
     * @param  string $name
     * @return Dominion
     */
    public function create(User $user/*, Realm $realm, Race $race*/, $name)
    {
        // todo: validate name?

        $attributes = [
            'user_id' => $user->id,
//            'round_id' => $realm->round_id,
//            'realm_id' => $realm->id,
//            'race_id' => $race->id,
            'name' => $name,
        ];

        // Gamevars
        foreach (gamevar('newdominion') as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $key2 => $value2) {
                    $attributes[$key . '_' . $key2] = $value2;
                }

                continue;
            }

            $attributes[$key] = $value;
        }

//        $attributes[$race->getHomeLand()] += $attributes['home_land_extra'];
        unset($attributes['home_land_extra']);

        $dominion = $this->dominion->create($attributes);

//        $user->active_dominion_id = $dominion->id;
        // todo: in dominion created hook?

        return $dominion;
    }
}
