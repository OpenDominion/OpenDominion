<?php

namespace OpenDominion\Repositories;

use OpenDominion\Models\Round;
use Prettus\Repository\Eloquent\BaseRepository;

class RoundRepository extends BaseRepository
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return Round::class;
    }
}
