<?php

namespace OpenDominion\Helpers;

use LogicException;
use OpenDominion\Models\Dominion;

class NotificationHelper
{
    /** @var SpellHelper */
    protected $spellHelper;

    public function __construct()
    {
        $this->spellHelper = app(SpellHelper::class);
    }

    public function getNotificationCategories(): array
    {
        return [
            'general' => $this->getGeneralTypes(),
            'hourly_dominion' => $this->getHourlyDominionTypes(),
            'irregular_dominion' => $this->getIrregularDominionTypes(),
//            'irregular_realm' => $this->getIrregularRealmTypes(),
        ];
    }

    public function getNotificationTypeLabel(string $key): string
    {
        return [
            'general' => 'General Notifications',
            'hourly_dominion' => 'Hourly Dominion Notifications',
            'irregular_dominion' => 'Irregular Dominion Notifications',
//            'irregular_realm' => 'Irregular Realm Notifications',
        ][$key];
    }

    public function getGeneralTypes(): array
    {
        return [
            // updates
            // anouncements
            'generic' => [
                'label' => 'Generic emails manually sent by the administrators',
                'onlyemail' => true,
                'defaults' => ['email' => true],
            ]
        ];
    }

    public function getHourlyDominionTypes(): array
    {
        return [
            'exploration_completed' => [
                'label' => 'Land exploration completed',
                'defaults' => ['email' => false, 'ingame' => true],
                'route' => route('dominion.explore'),
                'iconClass' => 'fa fa-search text-green',
            ],
            'construction_completed' => [
                'label' => 'Building construction completed',
                'defaults' => ['email' => false, 'ingame' => true],
                'route' => route('dominion.construct'),
                'iconClass' => 'fa fa-home text-green',
            ],
            'training_completed' => [
                'label' => 'Military training completed',
                'defaults' => ['email' => false, 'ingame' => true],
                'route' => route('dominion.military'),
                'iconClass' => 'ra ra-muscle-up text-green',
            ],
            'returning_completed' => [
                'label' => 'Units returned from battle',
                'defaults' => ['email' => false, 'ingame' => true],
                'route' => route('dominion.military'),
                'iconClass' => 'ra ra-player-dodge text-green',
            ],
            'beneficial_magic_dissipated' => [
                'label' => 'Beneficial magic effect dissipated',
                'defaults' => ['email' => false, 'ingame' => true],
                'route' => route('dominion.magic'),
                'iconClass' => 'ra ra-fairy-wand text-orange',
            ],
            'harmful_magic_dissipated' => [
                'label' => 'Harmful magic effect dissipated',
                'defaults' => ['email' => false, 'ingame' => true],
                'iconClass' => 'ra ra-fairy-wand text-green',
            ],
            'starvation_occurred' => [
                'label' => 'Starvation occurred',
                'defaults' => ['email' => false, 'ingame' => true],
                'route' => route('dominion.advisors.production'),
                'iconClass' => 'ra ra-tombstone text-red',
            ],
        ];
    }

    public function getIrregularDominionTypes(): array
    {
        return [
            'received_invasion' => [
                'label' => 'Your dominion got invaded',
                'defaults' => ['email' => false, 'ingame' => true],
                'route' => function (array $routeParams) {
                    return route('dominion.event', $routeParams);
                },
                'iconClass' => 'ra ra-crossed-swords text-red',
            ],
            'repelled_invasion' => [
                'label' => 'Your dominion repelled an invasion',
                'defaults' => ['email' => false, 'ingame' => true],
                'route' => function (array $routeParams) {
                    return route('dominion.event', $routeParams);
                },
                'iconClass' => 'ra ra-crossed-swords text-orange',
            ],
            'received_spy_op' => [
                'label' => 'Hostile spy operation received',
                'defaults' => ['email' => false, 'ingame' => true],
                'iconClass' => 'fa fa-user-secret text-orange',
            ],
            'repelled_spy_op' => [
                'label' => 'Hostile spy operation repelled',
                'defaults' => ['email' => false, 'ingame' => true],
                'iconClass' => 'fa fa-user-secret text-orange',
            ],
            'resource_theft' => [
                'label' => 'Resource stolen',
                'defaults' => ['email' => false, 'ingame' => true],
                'iconClass' => 'fa fa-user-secret text-orange',
            ],
            'repelled_resource_theft' => [
                'label' => 'Resource theft repelled',
                'defaults' => ['email' => false, 'ingame' => true],
                'iconClass' => 'fa fa-user-secret text-orange',
            ],
            'received_hostile_spell' => [
                'label' => 'Hostile spell received',
                'defaults' => ['email' => false, 'ingame' => true],
                'iconClass' => 'ra ra-fairy-wand text-orange',
            ],
            'repelled_hostile_spell' => [
                'label' => 'Hostile spell deflected',
                'defaults' => ['email' => false, 'ingame' => true],
                'iconClass' => 'ra ra-fairy-wand text-orange',
            ],
//            'scripted' => [
//                'label' => 'Land you conquered got removed due to anti-cheating mechanics (scripted)',
//                'defaults' => ['email' => false, 'ingame' => true],
//            ],
        ];
    }

    public function getIrregularRealmTypes(): array
    {
        return [
            'realmie_performed_info_ops' => [
                'label' => 'A realmie performed info ops',
                'defaults' => ['email' => false, 'ingame' => true],
            ],
            'realmie_performed_black_ops' => [
                'label' => 'A realmie performed black ops',
                'defaults' => ['email' => false, 'ingame' => true],
            ],
            'realmie_invaded_enemy_success' => [
                'label' => 'A realmie successfuly invaded an enemy',
                'defaults' => ['email' => false, 'ingame' => true],
            ],
            'realmie_invaded_enemy_fail' => [
                'label' => 'A realmie failed to invade an enemy',
                'defaults' => ['email' => false, 'ingame' => true],
            ],
            'enemy_invaded_realmie' => [
                'label' => 'An enemy invaded a realmie',
                'defaults' => ['email' => false, 'ingame' => true],
            ],
            'enemy_realm_declared_war' => [
                'label' => 'An enemy realm declared war upon our realm',
                'defaults' => ['email' => false, 'ingame' => true],
            ],
            'declared_war_upon_enemy_realm' => [
                'label' => 'Our realm declared war upon an enemy realm',
                'defaults' => ['email' => false, 'ingame' => true],
            ],
            'wonder_attacked' => [
                'label' => 'A wonder our realm controls was attacked',
                'defaults' => ['email' => false, 'ingame' => true],
            ],
            'wonder_destroyed' => [
                'label' => 'A wonder our realm controls was destroyed',
                'defaults' => ['email' => false, 'ingame' => true],
            ],
            'realmie_death' => [
                'label' => 'A realmie has died',
                'defaults' => ['email' => false, 'ingame' => true],
            ],
        ];
    }

    public function getDefaultUserNotificationSettings(): array
    {
        return collect($this->getNotificationCategories())->map(function ($notifications) {
            $return = [];

            foreach ($notifications as $key => $notification) {
                $return[$key] = $notification['defaults'] ?? 'nyi';
            }

            return $return;
        })->toArray();
    }

    public function getNotificationMessage(string $category, string $type, array $data): string
    {
        switch ("{$category}.{$type}") {

            case 'hourly_dominion.exploration_completed':
                $acres = array_sum($data);

                return sprintf(
                    'Exploration for %s %s of land completed',
                    number_format($acres),
                    str_plural('acre', $acres)
                );

            case 'hourly_dominion.construction_completed':
                $buildings = array_sum($data);

                return sprintf(
                    'Construction of %s %s completed',
                    number_format($buildings),
                    str_plural('building', $buildings)
                );

            case 'hourly_dominion.training_completed':
                $units = array_sum($data);

                return sprintf(
                    'Training of %s %s completed',
                    number_format($units),
                    str_plural('unit', $units)
                );

            case 'hourly_dominion.returning_completed':
                $units = array_sum($data);

                return sprintf(
                    '%s %s returned from battle',
                    number_format($units),
                    str_plural('unit', $units)
                );

            case 'hourly_dominion.beneficial_magic_dissipated':
                $effects = count($data);

                return sprintf(
                    '%s beneficial magic %s dissipated',
                    number_format($effects),
                    str_plural('effect', $effects)
                );

            case 'hourly_dominion.harmful_magic_dissipated':
                $effects = count($data);

                return sprintf(
                    '%s harmful magic %s dissipated',
                    number_format($effects),
                    str_plural('effect', $effects)
                );

            case 'hourly_dominion.starvation_occurred':
                $units = array_sum($data);

                return sprintf(
                    '%s %s died due to starvation',
                    number_format($units),
                    str_plural('unit', $units)
                );

            case 'irregular_dominion.received_invasion':
                $attackerDominion = Dominion::with('realm')->findOrFail($data['attackerDominionId']);

                return sprintf(
                    'An army from %s (#%s) invaded our lands, conquering %s acres of land! We lost %s units during the battle.',
                    $attackerDominion->name,
                    $attackerDominion->realm->number,
                    number_format($data['landLost']),
                    number_format($data['unitsLost'])
                );

            case 'irregular_dominion.repelled_invasion':
                $attackerDominion = Dominion::with('realm')->findOrFail($data['attackerDominionId']);

                return sprintf(
                    'Forces from %s (#%s) invaded our lands, but our army drove them back! We lost %s units during the battle.',
                    $attackerDominion->name,
                    $attackerDominion->realm->number,
                    number_format($data['unitsLost'])
                );

            case 'irregular_dominion.received_spy_op':
                $sourceDominion = Dominion::with('realm')->find($data['sourceDominionId']);

                switch ($data['operationKey']) {
                    case 'barracks_spy':
                        $where = 'within our barracks';
                        break;

                    case 'castle_spy':
                        $where = 'within our castle';
                        break;

                    case 'survey_dominion':
                        $where = 'amongst our buildings';
                        break;

                    case 'land_spy':
                        $where = 'amongst our lands';
                        break;

                    default:
                        $where = 'somewhere';//throw new \LogicException("Received spy op notification for operation key {$data['operationKey']} not yet implemented");
                }

                if ($sourceDominion) {
                    return sprintf(
                        'Our wizards have determined that spies from %s (#%s) were %s!',
                        $sourceDominion->name,
                        $sourceDominion->realm->number,
                        $where
                    );
                }

                return sprintf(
                    'Our spies have detected a %s',
                    $data['operationKey']
                );

            case 'irregular_dominion.repelled_spy_op':
                $sourceDominion = Dominion::with('realm')->findOrFail($data['sourceDominionId']);

                switch ($data['operationKey']) {
                    case 'barracks_spy':
                        $where = 'within our barracks';
                        break;

                    case 'castle_spy':
                        $where = 'within our castle';
                        break;

                    case 'survey_dominion':
                        $where = 'amongst our buildings';
                        break;

                    case 'land_spy':
                        $where = 'amongst our lands';
                        break;

                    default:
                        throw new \LogicException("Repelled spy op notification for operation key {$data['operationKey']} not yet implemented");
                }

                $lastPart = '';
                if ($data['unitsKilled']) {
                    $lastPart = "We executed {$data['unitsKilled']}.";
                }

                return sprintf(
                    'Spies from %s (#%s) were discovered %s! %s',
                    $sourceDominion->name,
                    $sourceDominion->realm->number,
                    $where,
                    $lastPart
                );

            case 'irregular_dominion.resource_theft':
                $sourceDominion = Dominion::with('realm')->find($data['sourceDominionId']);

                switch ($data['operationKey']) {
                    case 'steal_platinum':
                        $where = 'from our vaults';
                        break;

                    case 'steal_food':
                        $where = 'from our granaries';
                        break;

                    case 'steal_lumber':
                        $where = 'from our lumberyards';
                        break;

                    case 'steal_mana':
                        $where = 'from our towers';
                        break;

                    case 'steal_ore':
                    case 'steal_gems':
                        $where = 'from our mines';
                        break;

                    default:
                        throw new \LogicException("Resource theft op notification for operation key {$data['operationKey']} not yet implemented");
                }

                if ($sourceDominion) {
                    return sprintf(
                        'Our wizards have determined that spies from %s (#%s) stole %s %s %s!',
                        $sourceDominion->name,
                        $sourceDominion->realm->number,
                        number_format($data['amount']),
                        $data['resource'],
                        $where
                    );
                }

                return sprintf(
                    'Our spies discovered %s %s missing %s!',
                    number_format($data['amount']),
                    $data['resource'],
                    $where
                );

            case 'irregular_dominion.repelled_resource_theft':
                $sourceDominion = Dominion::with('realm')->findOrFail($data['sourceDominionId']);

                switch ($data['operationKey']) {
                    case 'steal_platinum':
                        $where = 'within our vaults';
                        break;

                    case 'steal_food':
                        $where = 'within our granaries';
                        break;

                    case 'steal_lumber':
                        $where = 'within our lumberyards';
                        break;

                    case 'steal_mana':
                        $where = 'within our towers';
                        break;

                    case 'steal_ore':
                        $where = 'within our ore mines';
                        break;

                    case 'steal_gems':
                        $where = 'within our diamond mines';
                        break;

                    default:
                        throw new \LogicException("Repelled resource theft op notification for operation key {$data['operationKey']} not yet implemented");
                }

                $lastPart = '';
                if ($data['unitsKilled']) {
                    $lastPart = "We executed {$data['unitsKilled']}.";
                }

                return sprintf(
                    'Spies from %s (#%s) were discovered %s! %s',
                    $sourceDominion->name,
                    $sourceDominion->realm->number,
                    $where,
                    $lastPart
                );

            case 'irregular_dominion.received_hostile_spell':
                $sourceDominion = Dominion::with('realm')->findOrFail($data['sourceDominionId']);

                return sprintf(
                    'Our wizards detected a %s spell cast by %s (#%s)!',
                    $this->spellHelper->getSpellInfo($data['spellKey'], $sourceDominion->race)['name'],
                    $sourceDominion->name,
                    $sourceDominion->realm->number
                );

            case 'irregular_dominion.repelled_hostile_spell':
                $sourceDominion = Dominion::with('realm')->findOrFail($data['sourceDominionId']);

                return sprintf(
                    'Our wizards have repelled a %s spell attempt by %s (#%s)!',
                    $this->spellHelper->getSpellInfo($data['spellKey'], $sourceDominion->race)['name'],
                    $sourceDominion->name,
                    $sourceDominion->realm->number
                );

            // todo: other irregular etc

            default:
                throw new LogicException("Unknown WebNotification message for {$category}.{$type}");
        }

        // exploration/construction/training/returning = sum
        // spell = spell name
        // invasion/spyop/spell = other dom name
        // scripted = sum/amount of acres
        // realmie invasion = instigator, target
        // war = other realm name
        // wonder = wondername, attacker
        // realmie death = realmie dom name
    }

    // todo: remove
    public function getIrregularTypes(): array
    {
        return [ // todo
            'Your dominion was invaded',
            // An army from Penrhyndeudraeth (# 14) invaded our lands, conquering 681 acres of land! We lost 237 draftees, 0 Slingers, 2647 Defenders, 643 Staff Masters and 3262 Master Thieves during the battle.
            'Your dominion repelled an invasion',
            // Forces from Night (# 42) invaded our lands, but our army drove them back! We lost 44 draftees, 0 Soldiers, 251 Miners, 199 Clerics and 0 Warriors during the battle.
            'Hostile spy op received',
            // 1 boats have sunk mysteriously while docked. | 145 draftees were killed while they slept in the barracks
            'Hostile spy op repelled',
            // Spies from Need more COWBELLT! (# 5) were discovered within the (draftee barracks | castle | vaults | docks/harbor?)! We executed 40 spies.
            'Hostile spell received',
            // ???
            'Hostile spell deflected',
            // Our wizards have repelled a Clear Sight spell attempt by And Thee Lord Taketh Away (# 21)!

            // Page: OP Center
            'Realmie performed info gathering spy op/spell',

            // Page: Town Crier
            'Realmie invaded another dominion',
            // Victorious on the battlefield, Priapus (# 16) conquered 64 land from Black Whirling (# 26).
            'Dominion failed to invade realmie',
            // Fellow dominion Jupiter (# 11) fended of an attack from Miss Piggy (# 31).
            'Realmie failed to invade another dominion',
            // Sadly, the forces of Starscream (# 31) were beaten back by Myself Yourself (# 44).
            'A dominion invaded realmie',
            // Battle Rain (# 29) invaded slow.internet.guy (# 16) and captured 440 land.
            'Our realm delared war upon another realm',
            // We have declared WAR on Rise of the Dragons (# 9)!
            'A realm has declared war upon us',
            // Golden Dragons (# 9) has declared WAR on us!
            'our wonder attacked',
            // Dirge (# 31) has attacked the Temple of the Damned!
            'our wonder destroyed',
            // The Temple of the Blessed has been destroyed and rebuilt by Realm #16!
            'death',
            // Cruzer (# 2) has been abandoned by its ruler.
        ];

        // after successful invasion:
        // Your army fights valiantly, and defeats the forces of Darth Vader, conquering 403 new acres of land! During the invasion, your troops also discovered 201 acres of land.

        // after failed invasion:
        // ???

        // after successful invasion, some racial effects:
        // In addition, your army converts some of the enemy casualties into 0 Skeletons, 0 Ghouls and 995 Progeny!
        // In addition, your Garous convert some of the enemy into 2781 werewolves to fight for our army!

        // Being scripted:
        // The game has automatically removed 43 acres of land because of apparent land farming of Elysia (# 9).

    }
}
