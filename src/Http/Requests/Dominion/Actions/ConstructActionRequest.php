<?php

namespace OpenDominion\Http\Requests\Dominion\Actions;

use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Http\Requests\Dominion\AbstractDominionRequest;

class ConstructActionRequest extends AbstractDominionRequest
{
    /** @var BuildingHelper */
    protected $buildingHelper;

    /**
     * ConstructActionRequest constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->buildingHelper = app(BuildingHelper::class);
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        $rules = [];

        foreach ($this->buildingHelper->getBuildingTypes() as $buildingType) {
            $rules['construct.' . $buildingType] = 'integer|nullable|min:0';
        }

        return $rules;
    }
}
