<?php

namespace OpenDominion\HeroCombat\Content\Effects\Boss;

class AdmiralsOrders extends AbstractTelegraph
{
    public function key(): string
    {
        return 'admirals_orders';
    }

    public function name(): string
    {
        return 'Admiral\'s Orders';
    }

    protected function moves(): array
    {
        return ['broadside', 'rally_the_defenders', 'admirals_challenge'];
    }

    protected function tells(): array
    {
        return [
            'broadside' => '{actor} raises his hand. Gunports slide open along the side of his ship.',
            'rally_the_defenders' => '{actor} fills his lungs and turns toward his ship.',
            'admirals_challenge' => '{actor} levels his cutlass at you, and the crew begins to jeer.',
        ];
    }
}
