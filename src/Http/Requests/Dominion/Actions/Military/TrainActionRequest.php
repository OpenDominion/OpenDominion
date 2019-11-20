<?php

namespace OpenDominion\Http\Requests\Dominion\Actions\Military;

use OpenDominion\Helpers\UnitHelper;
use OpenDominion\Http\Requests\Dominion\AbstractDominionRequest;

class TrainActionRequest extends AbstractDominionRequest
{
    /** @var UnitHelper */
    protected $unitHelper;

    /**
     * TrainActionRequest constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->unitHelper = app(UnitHelper::class);
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        $rules = [];

        foreach ($this->unitHelper->getUnitTypes() as $unitType) {
            $rules['train.' . $unitType] = 'integer|nullable|min:0';
        }

        return $rules;
    }
}
