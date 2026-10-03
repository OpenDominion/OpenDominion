<?php

namespace OpenDominion\HeroCombat\Engine\Effects;

enum StackingRule: string
{
    /** Reapplying resets the duration and merges data. */
    case Refresh = 'refresh';
    /** Reapplying adds stacks (up to the max) and resets the duration. */
    case Stack = 'stack';
    /** Reapplying removes the old instance and adds a new one. */
    case Replace = 'replace';
    /** Every application is its own instance. */
    case Independent = 'independent';
}
