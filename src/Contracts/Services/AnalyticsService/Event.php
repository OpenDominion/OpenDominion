<?php

namespace OpenDominion\Contracts\Services\AnalyticsService;

interface Event
{
    public function getCategory();

    public function getAction();

    public function getLabel();

    public function getValue();
}
