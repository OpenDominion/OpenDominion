<?php

namespace OpenDominion\Http\Requests\Dominion\Actions;

use Auth;
use Illuminate\Foundation\Http\FormRequest;
use OpenDominion\Helpers\LandHelper;

class ExploreActionRequest extends FormRequest
{
    /** @var LandHelper */
    protected $landHelper;

    public function __construct()
    {
        $this->landHelper = app()->make(LandHelper::class);
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // todo: dominion selected, selected dominion in active round?
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
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
