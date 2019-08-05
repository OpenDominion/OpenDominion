<?php

namespace OpenDominion\Models\Dominion;

use OpenDominion\Models\AbstractModel;

/**
 * OpenDominion\Models\Dominion\Tick
 *
 * @property int $id
 * @property int $dominion_id
 * @property int $prestige
 * @property int $peasants
 * @property int $morale
 * @property float $spy_strength
 * @property float $wizard_strength
 * @property int $resource_platinum
 * @property int $resource_food
 * @property int $resource_lumber
 * @property int $resource_mana
 * @property int $resource_ore
 * @property int $resource_gems
 * @property int $resource_tech
 * @property float $resource_boats
 * @property int $military_draftees
 * @property int $military_unit1
 * @property int $military_unit2
 * @property int $military_unit3
 * @property int $military_unit4
 * @property int $military_spies
 * @property int $military_wizards
 * @property int $military_archmages
 * @property int $land_plain
 * @property int $land_mountain
 * @property int $land_swamp
 * @property int $land_cavern
 * @property int $land_forest
 * @property int $land_hill
 * @property int $land_water
 * @property int $building_home
 * @property int $building_alchemy
 * @property int $building_farm
 * @property int $building_smithy
 * @property int $building_masonry
 * @property int $building_ore_mine
 * @property int $building_gryphon_nest
 * @property int $building_tower
 * @property int $building_wizard_guild
 * @property int $building_temple
 * @property int $building_diamond_mine
 * @property int $building_school
 * @property int $building_lumberyard
 * @property int $building_forest_haven
 * @property int $building_factory
 * @property int $building_guard_tower
 * @property int $building_shrine
 * @property int $building_barracks
 * @property int $building_dock
 * @property-read \OpenDominion\Models\Dominion $dominion
 */
class Tick extends AbstractModel
{
    protected $table = 'dominion_tick';

    protected $casts = [
        'prestige' => 'integer',
        'peasants' => 'integer',
        'morale' => 'integer',
        'spy_strength' => 'float',
        'wizard_strength' => 'float',
        'resource_platinum' => 'integer',
        'resource_food' => 'integer',
        'resource_lumber' => 'integer',
        'resource_mana' => 'integer',
        'resource_ore' => 'integer',
        'resource_gems' => 'integer',
        'resource_tech' => 'integer',
        'resource_boats' => 'float',
        'military_draftees' => 'integer',
        'military_unit1' => 'integer',
        'military_unit2' => 'integer',
        'military_unit3' => 'integer',
        'military_unit4' => 'integer',
        'military_spies' => 'integer',
        'military_wizards' => 'integer',
        'military_archmages' => 'integer',
        'land_plain' => 'integer',
        'land_mountain' => 'integer',
        'land_swamp' => 'integer',
        'land_cavern' => 'integer',
        'land_forest' => 'integer',
        'land_hill' => 'integer',
        'land_water' => 'integer',
        'building_home' => 'integer',
        'building_alchemy' => 'integer',
        'building_farm' => 'integer',
        'building_smithy' => 'integer',
        'building_masonry' => 'integer',
        'building_ore_mine' => 'integer',
        'building_gryphon_nest' => 'integer',
        'building_tower' => 'integer',
        'building_wizard_guild' => 'integer',
        'building_temple' => 'integer',
        'building_diamond_mine' => 'integer',
        'building_school' => 'integer',
        'building_lumberyard' => 'integer',
        'building_forest_haven' => 'integer',
        'building_factory' => 'integer',
        'building_guard_tower' => 'integer',
        'building_shrine' => 'integer',
        'building_barracks' => 'integer',
        'building_dock' => 'integer',
    ];

    protected $guarded = ['id'];

    public function dominion()
    {
        return $this->belongsTo(\OpenDominion\Models\Dominion::class);
    }

    const CREATED_AT = null;
    const UPDATED_AT = null;
}
