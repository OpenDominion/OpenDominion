<?php

namespace OpenDominion\Http\Requests\Dominion\Actions;

use OpenDominion\Helpers\ImprovementHelper;
use OpenDominion\Http\Requests\Dominion\AbstractDominionRequest;

class ImproveActionRequest extends AbstractDominionRequest
{
    /** @var ImprovementHelper */
    protected $improvementHelper;

    /**
     * ImproveActionRequest constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->improvementHelper = app(ImprovementHelper::class);
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        $rules = [];

        foreach ($this->improvementHelper->getImprovementTypes() as $improvementType) {
            $rules['improve.' . $improvementType] = 'integer|nullable|min:0';
        }

        return $rules;
    }
}
