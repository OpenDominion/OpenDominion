<?php

namespace OpenDominion\Http\Requests\Dominion\Actions;

use OpenDominion\Helpers\LandHelper;
use OpenDominion\Http\Requests\Dominion\AbstractDominionRequest;

class ExploreActionRequest extends AbstractDominionRequest
{
    /** @var LandHelper */
    protected $landHelper;

    /**
     * ExploreActionRequest constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->landHelper = app(LandHelper::class);
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        $rules = [];

        foreach ($this->landHelper->getLandTypes() as $landType) {
            $rules['explore.' . $landType] = 'integer|nullable';
        }

        return $rules;
    }
}
