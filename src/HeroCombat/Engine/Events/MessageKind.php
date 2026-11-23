<?php

namespace OpenDominion\HeroCombat\Engine\Events;

/**
 * What a logged message adds to the turn's structured events.
 */
enum MessageKind: string
{
    /** Narration with no structured counterpart: stances, telegraphs, flavour. */
    case Narration = 'narration';

    /** Restates a damage or heal event, so turn summaries leave it out. */
    case Detail = 'detail';

    /** How the battle ended. */
    case Outcome = 'outcome';

    /** A tell: what an enemy will do on a coming turn. Shown like narration, and again above the orders. */
    case Telegraph = 'telegraph';
}
