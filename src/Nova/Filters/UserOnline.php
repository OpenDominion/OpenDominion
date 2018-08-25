<?php

namespace OpenDominion\Nova\Filters;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Laravel\Nova\Filters\Filter;

class UserOnline extends Filter
{
    /**
     * The displayable name of the action.
     *
     * @var string
     */
    public $name = 'Online Users';

    /**
     * Apply the filter to the given query.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param  mixed $value
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function apply(Request $request, $query, $value)
    {
        return (($value === 'online')
            ? $query->whereNotNull('last_online')
                ->where('last_online', '>', (new Carbon('-5 minutes'))->format('Y-m-d H:i:s'))
            : $query->whereNull('last_online')
                ->orWhere('last_online', '<=', (new Carbon('-5 minutes'))->format('Y-m-d H:i:s'))
        );
    }

    /**
     * Get the filter's available options.
     *
     * @param  \Illuminate\Http\Request $request
     * @return array
     */
    public function options(Request $request)
    {
        return [
            'Online Users' => 'online',
            'Offline Users' => 'offline',
        ];
    }
}
