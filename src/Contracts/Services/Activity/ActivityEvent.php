<?php

namespace OpenDominion\Contracts\Services\Activity;

interface ActivityEvent
{
    const STATUS_SUCCESS = 'success';
    const STATUS_INFO = 'info';
    const STATUS_GENERIC = 'generic';
    const STATUS_WARNING = 'warning';
    const STATUS_DANGER = 'danger';

    /**
     * @return string
     */
    public function getKey(): string;

    /**
     * @return string
     */
    public function getStatus(): string;

    /**
     * @return array
     */
    public function getContext(): array;
}
