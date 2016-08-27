<?php

namespace OpenDominion\Repositories;

use OpenDominion\Models\Race;
use Prettus\Repository\Eloquent\BaseRepository;

class RaceRepository extends BaseRepository
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return Race::class;
    }
}
