<?php

namespace OpenDominion\Http\Requests\Dominion\Actions;

use OpenDominion\Helpers\LandHelper;
use OpenDominion\Http\Requests\AbstractRequest;

class RezoneActionRequest extends AbstractRequest
{
    /** @var LandHelper */
    protected $landHelper;

    /**
     * RezoneActionRequest constructor.
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
            $rules['remove.' . $landType] = 'integer|nullable';
            $rules['add.' . $landType] = 'integer|nullable';
        }

        return $rules;
    }
}
