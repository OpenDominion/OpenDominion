<?php

namespace OpenDominion\Repositories;

use OpenDominion\Models\Dominion;
use Prettus\Repository\Eloquent\BaseRepository;

class DominionRepository extends BaseRepository
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return Dominion::class;
    }
}
