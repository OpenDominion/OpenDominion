<?php

namespace OpenDominion\Contracts\Services\AnalyticsService;

interface Event
{
    public function getCategory(): string;

    public function getAction(): string;

    public function getLabel(): ?string;

    public function getValue(): ?int;
}
