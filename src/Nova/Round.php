<?php

namespace OpenDominion\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Text;

class Round extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \OpenDominion\Models\Round::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'name';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'name',
        'number',
    ];

    /**
     * Get the search result subtitle for the resource.
     *
     * @return string
     */
    public function subtitle()
    {
        return $this->league->description;
    }

    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Illuminate\Http\Request $request
     * @return array
     */
    public function fields(Request $request)
    {
        return [
            Number::make('Number')
                ->sortable(),

            Text::make('Name')
                ->rules(['required', 'max:255']),

            Number::make('Dominions', function () {
                return $this->dominions->count();
            })->onlyOnIndex(),

            BelongsTo::make('RoundLeague', 'league'),

            Number::make('Realm Size')
                ->hideFromIndex(),

            Number::make('Pack Size')
                ->hideFromIndex(),

            HasMany::make('Dominions'),
        ];
    }
}
