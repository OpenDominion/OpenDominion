<?php

namespace OpenDominion\Repositories\Criteria\Dominion;

use Auth;
use Prettus\Repository\Contracts\CriteriaInterface;
use Prettus\Repository\Contracts\RepositoryInterface;

class DominionFromCurrentLoggedInUserCriteria implements CriteriaInterface
{
    public function apply($model, RepositoryInterface $repository)
    {
        return $model->where('user_id', Auth::user()->id);
    }
}
