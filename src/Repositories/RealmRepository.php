<?php

namespace OpenDominion\Repositories;

use OpenDominion\Models\Realm;
use Prettus\Repository\Eloquent\BaseRepository;

class RealmRepository extends BaseRepository
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return Realm::class;
    }
}
