<?php

namespace OpenDominion\Services\Analytics;

class AnalyticsEvent
{
    /** @var string */
    protected $category;

    /** @var string */
    protected $action;

    /** @var string */
    protected $label;

    /** @var int */
    protected $value;

    /**
     * AnalyticsEvent constructor.
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
     * @return string|null
     */
    public function getLabel(): ?string
    {
        return $this->label;
    }

    /**
     * @return int}null
     */
    public function getValue(): ?int
    {
        return $this->value;
    }
}
