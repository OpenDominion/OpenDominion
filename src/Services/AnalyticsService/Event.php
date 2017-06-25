<?php

namespace OpenDominion\Services\AnalyticsService;

use OpenDominion\Contracts\Services\AnalyticsService\Event as EventContract;

class Event implements EventContract
{
    /** @var string */
    public $category;

    /** @var string */
    public $action;

    /** @var null|string */
    public $label;

    /** @var int|null */
    public $value;

    /**
     * Event constructor.
     *
     * @param string $category
     * @param string $action
     * @param string|null $label
     * @param int|null $value
     */
    public function __construct($category, $action, $label = null, $value = null)
    {
        $this->category = $category;
        $this->action = $action;
        $this->label = $label;
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @return null|string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @return int|null
     */
    public function getValue()
    {
        return $this->value;
    }
}
