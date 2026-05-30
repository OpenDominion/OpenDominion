<?php

namespace OpenDominion\Http\Requests\MessageBoard;

use Auth;
use OpenDominion\Http\Requests\AbstractRequest;

class EditThreadRequest extends AbstractRequest
{
    public function authorize()
    {
        return Auth::check() && Auth::user()->hasRole('Administrator');
    }

    public function rules()
    {
        return [
            'title' => 'required|string|max:80',
            'body' => 'required|string|max:20000',
            'homepage_display' => 'sometimes|boolean',
            'homepage_preset' => 'nullable|in:announcement,round,patch,event',
            'homepage_subtitle' => 'nullable|string|max:255',
            'homepage_url' => 'nullable|url|max:255',
        ];
    }
}
