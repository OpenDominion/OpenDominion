<?php

namespace OpenDominion\Notifications\Dominion;

use Illuminate\Notifications\Notification;
use OpenDominion\Models\Dominion;

class LandExploredNotification extends Notification
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function via(Dominion $dominion): array
    {
        return ['database'];
    }

    public function toArray(Dominion $dominion): array
    {
        $acres = array_sum($this->data);

        return [
            'message' => sprintf(
                'Exploration of %s %s of land completed.',
                number_format($acres),
                str_plural('acre', $acres)
            ),
        ];
    }
}
