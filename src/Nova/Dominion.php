<?php

namespace OpenDominion\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;

class Dominion extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \OpenDominion\Models\Dominion::class;

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
    ];

    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Illuminate\Http\Request $request
     * @return array
     */
    public function fields(Request $request)
    {
        return [
            ID::make()->sortable(),

            // user
            // round
            // realm
            // race
            // pack

            Text::make('Name')
                ->sortable(),

            Text::make('Ruler Name')
                ->hideFromIndex(),

            // todo: should be BelongsTo::make('User'), but gives 'class OpenUser\Nova\User not found', probably due to a bug
            BelongsTo::make('User', 'user', User::class)
                ->searchable(),

            BelongsTo::make('Round', 'round', Round::class),

            // prestige
            // peasants etc etc


        ];
    }
}