<?php

namespace OpenDominion\Services\Activity;

class ActivityEvent
{
    const STATUS_SUCCESS = 'success';
    const STATUS_INFO = 'info';
    const STATUS_GENERIC = 'generic';
    const STATUS_WARNING = 'warning';
    const STATUS_DANGER = 'danger';

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
    public function __construct(string $key, string $status = self::STATUS_GENERIC, array $context = [])
    {
        $this->key = $key;
        $this->status = $status;
        $this->context = $context;
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @return array
     */
    public function getContext(): array
    {
        return $this->context;
    }
}
