<?php namespace OpenDominion\Repositories;

use OpenDominion\Models\Dominion;

class DominionRepository extends Repository
{
    public function __construct(Dominion $model)
    {
        parent::__construct($model);
    }

    public function doesDominionWithEmailExist($name)
    {
        return ($this->model->where('name', $name)->count() > 0);
    }
}
