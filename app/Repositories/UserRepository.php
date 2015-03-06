<?php namespace OpenDominion\Repositories;

use OpenDominion\Models\User;

class UserRepository extends Repository
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    public function doesUserWithEmailExist($email)
    {
        return ($this->model->where('email', $email)->count() > 0);
    }
}
