<?php

namespace OpenDominion\Helpers;

class NotificationHelper
{
    public function getGeneralTypes(): array
    {
        return [
            // updates
            // anouncements
            'generic' => [
                'label' => 'Generic emails sent by the administrators',
                'defaults' => ['email' => true],
            ]
        ];
    }

    public function getHourlyDominionTypes(): array
    {
        return [
            'exploration_completed' => [
                'label' => 'Land exploration completed',
                'defaults' => ['web' => true, 'email' => true],
            ],
            'construction_completed' => [
                'label' => 'Building construction completed',
                'defaults' => ['web' => true, 'email' => true],
            ],
            'training_completed' => [
                'label' => 'Military training completed',
                'defaults' => ['web' => true, 'email' => false],
            ],
            'returning_completed' => [
                'label' => 'Units returned from battle',
                'defaults' => ['web' => true, 'email' => false],
            ],
            'beneficial_magic_dissipated' => [
                'label' => 'Beneficial magic effect dissipated',
                'defaults' => ['web' => true, 'email' => true],
            ],
            'harmful_magic_dissipated' => [
                'label' => 'Harmful magic effect dissipated',
                'defaults' => ['web' => true, 'email' => false],
            ],
            'starvation' => [
                'label' => 'Starvation occurred',
                'defaults' => ['web' => true, 'email' => true],
            ],
        ];
    }

    public function getIrregularDominionTypes(): array
    {
        return [
            'received_invasion' => [
                'label' => 'Your dominion got invaded',
                'defaults' => ['web' => true, 'email' => true],
            ],
            'repelled_invasion' => [
                'label' => 'Your dominion repelled an invasion',
                'defaults' => ['web' => true, 'email' => false],
            ],
            'received_spy_op' => [
                'label' => 'Hostile spy operation received',
                'defaults' => ['web' => true, 'email' => false],
            ],
            'repelled_spy_op' => [
                'label' => 'Hostile spy operation repelled',
                'defaults' => ['web' => true, 'email' => false],
            ],
            'received_hostile_spell' => [
                'label' => 'Hostile spell received',
                'defaults' => ['web' => true, 'email' => false],
            ],
            'repelled_hostile_spell' => [
                'label' => 'Hostile spell deflected',
                'defaults' => ['web' => true, 'email' => false],
            ],
        ];
    }

    public function getIrregularRealmTypes(): array
    {
        // todo
        return [
            'realmie_invaded_enemy_success' => [],
            'realmie_invaded_enemy_fail' => [],
            'enemy_invaded_realmie' => [],
            'enemy_realm_declared_war' => [],
            'declared_war_upon_enemy_realm' => [],
            'wonder_attacked' => [],
            'wonder_destroyed' => [],
            'realmie_death' => [],
        ];
    }

    public function getIrregularTypes(): array
    {
        return [ // todo
            'Your dominion was invaded', // An army from Penrhyndeudraeth (# 14) invaded our lands, conquering 681 acres of land! We lost 237 draftees, 0 Slingers, 2647 Defenders, 643 Staff Masters and 3262 Master Thieves during the battle.
            'Your dominion repelled an invasion', // Forces from Night (# 42) invaded our lands, but our army drove them back! We lost 44 draftees, 0 Soldiers, 251 Miners, 199 Clerics and 0 Warriors during the battle.
            'Hostile spy op received', // 1 boats have sunk mysteriously while docked. | 145 draftees were killed while they slept in the barracks
            'Hostile spy op repelled', // Spies from Need more COWBELLT! (# 5) were discovered within the (draftee barracks | castle | vaults | docks/harbor?)! We executed 40 spies.
            'Hostile spell received', // ???
            'Hostile spell deflected', // Our wizards have repelled a Clear Sight spell attempt by And Thee Lord Taketh Away (# 21)!

            // Page: OP Center
            'Realmie performed info gathering spy op/spell',

            // Page: Town Crier
            'Realmie invaded another dominion', // Victorious on the battlefield, Priapus (# 16) conquered 64 land from Black Whirling (# 26).
            'Realmie failed to invade another dominion', // Sadly, the forces of Starscream (# 31) were beaten back by Myself Yourself (# 44).
            'A dominion invaded realmie', // Battle Rain (# 29) invaded slow.internet.guy (# 16) and captured 440 land.
            'Our realm delared war upon another realm', // We have declared WAR on Rise of the Dragons (# 9)!
            'A realm has declared war upon us', // Golden Dragons (# 9) has declared WAR on us!
            'our wonder attacked', // Dirge (# 31) has attacked the Temple of the Damned!
            'our wonder destroyed', // The Temple of the Blessed has been destroyed and rebuilt by Realm #16!
            'death', // Cruzer (# 2) has been abandoned by its ruler.
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
