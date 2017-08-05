<?php

namespace OpenDominion\Http\Requests\Dominion;

use OpenDominion\Contracts\Services\Dominion\SelectorService;
use OpenDominion\Http\Requests\AbstractRequest;

abstract class AbstractDominionRequest extends AbstractRequest
{
    /** @var SelectorService */
    protected $selectorService;

    /**
     * AbstractDominionRequest constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->selectorService = app(SelectorService::class);
    }

    /**
     * {@inheritdoc}
     */
    public function authorize()
    {
        return (
            parent::authorize() &&
            $this->selectorService->hasUserSelectedDominion()
        );
    }
}
