<?php

namespace OpenDominion\Nova\Filters;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Laravel\Nova\Filters\Filter;

class UserOnline extends Filter
{
    public const FIELD = 'last_online';
    public const ONLINE_THRESHOLD = '-5 minutes';

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
            ? $query->whereNotNull(static::FIELD)
                ->where(static::FIELD, '>', (new Carbon(static::ONLINE_THRESHOLD))->format('Y-m-d H:i:s'))
            : $query->whereNull(static::FIELD)
                ->orWhere(static::FIELD, '<=', (new Carbon(static::ONLINE_THRESHOLD))->format('Y-m-d H:i:s'))
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
