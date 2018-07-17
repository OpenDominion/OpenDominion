<?php

namespace OpenDominion\Factories;

use OpenDominion\Models\Pack;
use OpenDominion\Models\Round;
use OpenDominion\Models\User;

class PackFactory
{
    public function create(Round $round, User $user, string $password, int $size): Pack
    {
        return Pack::create([
            'round_id' => $round->id,
            'user_id' => $user->id,
            'password' => $password,
            'size' => $size
        ]);
    }
}