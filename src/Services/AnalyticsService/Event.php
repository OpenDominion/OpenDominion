<?php

namespace OpenDominion\Services\AnalyticsService;

class Event
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
}
