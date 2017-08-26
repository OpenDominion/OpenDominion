<?php

namespace OpenDominion\Contracts\Services\Analytics;

interface AnalyticsEvent
{
    /**
     * @return string
     */
    public function getCategory(): string;

    /**
     * @return string
     */
    public function getAction(): string;

    /**
     * @return string
     */
    public function getLabel(): ?string;

    /**
     * @return int
     */
    public function getValue(): ?int;
}
