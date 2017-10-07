<?php

namespace OpenDominion\Http\Middleware;

use Analytics;
use Closure;
use OpenDominion\Services\Analytics\AnalyticsEvent;
use OpenDominion\Services\Analytics\AnalyticsService;

class FireAnalyticsFlashEvents
{
    /** @var AnalyticsService */
    protected $analyticsService;

    public function __construct(AnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    public function handle($request, Closure $next)
    {
        // todo: if request method === GET?

        foreach ($this->analyticsService->getFlashEvents() as $event) {
            /** @var AnalyticsEvent $event */
            Analytics::trackEvent(
                $event->getCategory(),
                $event->getAction(),
                $event->getLabel(),
                $event->getValue()
            );
        }

        return $next($request);
    }
}
