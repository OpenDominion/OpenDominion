<?php

namespace OpenDominion\Services\Analytics;

use OpenDominion\Contracts\Services\Analytics\AnalyticsEvent as AnalyticsEventContract;

class AnalyticsEvent implements AnalyticsEventContract
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
     * {@inheritdoc}
     */
    public function getCategory(): string
    {
        return $this->category;
    }

    /**
     * {@inheritdoc}
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel(): ?string
    {
        return $this->label;
    }

    /**
     * {@inheritdoc}
     */
    public function getValue(): ?int
    {
        return $this->value;
    }
}
