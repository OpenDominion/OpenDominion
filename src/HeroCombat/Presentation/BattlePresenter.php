<?php

namespace OpenDominion\HeroCombat\Presentation;

use OpenDominion\HeroCombat\Persistence\BattleRepository;
use OpenDominion\Models\HeroBattle;

class BattlePresenter
{
    public function __construct(protected BattleRepository $repository)
    {
    }

    public function present(HeroBattle $heroBattle, ?int $viewerHeroId = null): BattleView
    {
        $battle = $this->repository->load($heroBattle);

        $viewer = null;
        foreach ($battle->combatants() as $combatant) {
            if ($viewerHeroId !== null && $combatant->heroId === $viewerHeroId) {
                $viewer = $combatant;
                break;
            }
        }

        return new BattleView($heroBattle, $battle, $viewer);
    }
}
