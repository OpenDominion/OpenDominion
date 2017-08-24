<?php

namespace OpenDominion\Services\AnalyticsService;

use OpenDominion\Contracts\Services\AnalyticsService\Event as EventContract;

class Event implements EventContract
{
    /** @var string */
    public $category;

    /** @var string */
    public $action;

    /** @var string */
    public $label;

    /** @var int */
    public $value;

    /**
     * Event constructor.
     *
     * @param string $category
     * @param string $action
     * @param string $label
     * @param int $value
     */
    public function __construct(string $category, string $action, string $label = null, int $value = null)
    {
        $this->category = $category;
        $this->action = $action;
        $this->label = $label;
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getCategory(): string
    {
        return $this->category;
    }

    /**
     * @return string
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * @return string
     */
    public function getLabel(): ?string
    {
        return $this->label;
    }

    /**
     * @return int
     */
    public function getValue(): ?int
    {
        return $this->value;
    }
}
