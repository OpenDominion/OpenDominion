<?php

namespace OpenDominion\Http\Requests\Dominion\Actions;

use OpenDominion\Helpers\UnitHelper;
use OpenDominion\Http\Requests\Dominion\AbstractDominionRequest;

class ReleaseActionRequest extends AbstractDominionRequest
{
    /** @var UnitHelper */
    protected $unitHelper;

    /**
     * ReleaseActionRequest constructor.
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
            $rules['release.' . $unitType] = 'integer|nullable|min:0';
        }

        return $rules;
    }
}
