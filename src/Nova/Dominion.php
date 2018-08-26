<?php

namespace OpenDominion\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Panel;

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

            Text::make('Name')
                ->sortable(),

            Text::make('Ruler Name')
                ->hideFromIndex(),

            // todo: should be BelongsTo::make('User'), but gives 'class OpenUser\Nova\User not found', probably due to a bug
            BelongsTo::make('User', 'user', User::class)
                ->searchable(),

            BelongsTo::make('Round', 'round', Round::class),

            // realm
            // race
            // pack

            new Panel('Population', [
                Number::make('Prestige')->hideFromIndex(),
                Number::make('Peasants')->hideFromIndex(),
                Number::make('Peasants Last Hour')->hideFromIndex(),
            ]),

            new Panel('Military', [
                Number::make('Draft Rate')->hideFromIndex(), // %
                Number::make('Morale')->hideFromIndex(), // %
                Number::make('Spy Strength')->hideFromIndex(), // %
                Number::make('Wizard Strength')->hideFromIndex(), // %
            ]),

            new Panel('Resources', [
                Number::make('Platinum', 'resource_platinum')->hideFromIndex(),
                Number::make('Food', 'resource_food')->hideFromIndex(),
                Number::make('Lumber', 'resource_lumber')->hideFromIndex(),
                Number::make('Mana', 'resource_mana')->hideFromIndex(),
                Number::make('Ore', 'resource_ore')->hideFromIndex(),
                Number::make('Gems', 'resource_gems')->hideFromIndex(),
                Number::make('Tech', 'resource_tech')->hideFromIndex(),
                Number::make('Boats', 'resource_boats')->hideFromIndex()->step(0.01),
            ]),

            // improvements

            // military

            // land

            // buildings

            // #daily bonuses
            // daily plat
            // daily land
        ];
    }
}