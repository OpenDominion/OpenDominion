<?php

namespace OpenDominion\Services\Activity;

use OpenDominion\Contracts\Services\Activity\ActivityEvent as ActivityEventContract;

class ActivityEvent implements ActivityEventContract
{
    /** @var string */
    protected $key;

    /** @var string  */
    protected $status;

    /** @var array */
    protected $context;

    /**
     * ActivityEvent constructor.
     *
     * @param string $key
     * @param string $status
     * @param array $context
     */
    public function __construct(string $key, string $status = ActivityEventContract::STATUS_GENERIC, array $context = [])
    {
        $this->key = $key;
        $this->status = $status;
        $this->context = $context;
    }

    /**
     * {@inheritdoc}
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * {@inheritdoc}
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * {@inheritdoc}
     */
    public function getContext(): array
    {
        return $this->context;
    }
}
