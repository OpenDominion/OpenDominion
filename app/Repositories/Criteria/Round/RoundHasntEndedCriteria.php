<?php

namespace OpenDominion\Repositories\Criteria\Round;

use Carbon\Carbon;
use Prettus\Repository\Contracts\CriteriaInterface;
use Prettus\Repository\Contracts\RepositoryInterface;

class RoundHasntEndedCriteria implements CriteriaInterface
{
    public function apply($model, RepositoryInterface $repository)
    {
        return $model->where('end_date', '>', new Carbon('today'));
    }
}
