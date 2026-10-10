<?php

namespace OpenDominion\HeroCombat\Engine\Stats;

enum ModifierType: string
{
    case Flat = 'flat';
    case Percent = 'percent';
    case Override = 'override';
}
