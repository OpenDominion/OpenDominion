<?php

namespace OpenDominion\Services\Dominion;

use Illuminate\Support\Collection;
use OpenDominion\Calculators\Dominion\HeroCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Helpers\HeroHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Hero;
use OpenDominion\Models\HeroBattle;
use OpenDominion\Models\HeroBattleAction;
use OpenDominion\Models\HeroCombatant;
use OpenDominion\Models\Round;

class HeroBattleService
{
    /** @var HeroCalculator */
    protected $heroCalculator;

    /** @var HeroHelper */
    protected $heroHelper;

    /**
     * HeroBattleService constructor.
     *
     * @param HeroCalculator $heroCalculator
     * @param HeroHelper $heroHelper
     */
    public function __construct(
        HeroCalculator $heroCalculator,
        HeroHelper $heroHelper,
    )
    {
        $this->heroCalculator = $heroCalculator;
        $this->heroHelper = $heroHelper;
    }

    public const STARTING_TIME_BANK = 24 * 60 * 60;
    public const DEFAULT_STRATEGY = 'balanced';

    public function createBattle(Dominion $challenger, Dominion $opponent): HeroBattle
    {
        /* Move to action service
        if ($challenger->round_id !== $opponent->round_id) {
            throw GameException('You cannot challenge a dominion in a different round');
        }

        if ($challenger->dominion_id === $opponent->dominion_id) {
            throw GameException('You cannot challenge yourself');
        }
        */

        $challengerHero = $challenger->heroes()->first();
        if ($challengerHero === null) {
            throw GameException('Challanger must have a hero to battle');
        }

        $opponentHero = $opponent->heroes()->first();
        if ($opponentHero === null) {
            throw GameException('Opponent must have a hero to battle');
        }

        $heroBattle = HeroBattle::create(['round_id' => $challenger->round_id]);
        $challengerCombatant = $this->createCombatant($challengerHero, $heroBattle);
        $opponentCombatant = $this->createCombatant($opponentHero, $heroBattle);

        return $heroBattle;
    }

    public function createCombatant(Hero $hero, HeroBattle $heroBattle): HeroCombatant
    {
        $combatStats = $this->heroCalculator->getHeroCombatStats($hero);

        return HeroCombatant::create([
            'hero_battle_id' => $heroBattle->id,
            'hero_id' => $hero->id,
            'dominion_id' => $hero->dominion_id,
            'name' => $hero->name,
            'health' => $combatStats['health'],
            'attack' => $combatStats['attack'],
            'defense' => $combatStats['defense'],
            'evasion' => $combatStats['evasion'],
            'focus' => $combatStats['focus'],
            'counter' => $combatStats['counter'],
            'recover' => 0, // $combatStats['recover'],
            'current_health' => $combatStats['health'],
            'has_focus' => false,
            'last_action' => null,
            'time_bank' => self::STARTING_TIME_BANK,
            'actions' => null,
            'strategy' => self::DEFAULT_STRATEGY
        ]);
    }

    public function processBattles(Round $round): void
    {
        $battles = HeroBattle::query()
            ->where('round_id', $round->id)
            ->where('finished', false)
            ->get();

        foreach ($battles as $battle) {
            $this->checkTime($heroBattle);
            $this->processTurn($battle);
        }
    }

    public function checkTime(HeroBattle $heroBattle): void
    {
        foreach ($heroBattle->combatants as $combatant) {
            $combatant->time_bank -= $combatant->timeElapsed();
            if ($combatant->time_bank <= 0) {
                $combatant->automated = true;
            }
            $combatant->save();
        }

        $heroBattle->last_processed_at = now();
        $heroBattle->save();
    }

    public function processTurn(HeroBattle $heroBattle): bool
    {
        if ($heroBattle->finished) {
            return false;
        }

        $combatants = $heroBattle->combatants;

        // Eject if not all combatants are ready
        foreach ($combatants as $combatant) {
            if (!$combatant->isReady()) {
                return false;
            }
        }

        // Determine which action to take via queue or automated strategy
        foreach ($combatants as $combatant) {
            $combatant->current_action = $this->determineAction($combatant);
        }

        // Perform the actions and persist results
        foreach ($combatants as $combatant) {
            $target = $combatants->where('id', '!=', $combatant->id)->random();
            $result = $this->processAction($combatant, $target);

            $combatant->current_health += $result['health'];
            $target->current_health -= $result['damage'];

            $action = HeroBattleAction::create([
                'hero_battle_id' => $heroBattle->id,
                'combatant_id' => $combatant->id,
                'turn' => $heroBattle->current_turn,
                'action' => $combatant->current_action,
                'damage' => $result['damage'],
                'health' => $result['health'],
                'description' => $result['description']
            ]);
        }

        // Prepare combatants for next turn
        foreach ($combatants as $combatant) {
            if ($combatant->current_health > $combatant->health) {
                $combatant->current_health = $combatant->health;
            }
            $combatant->last_action = $combatant->current_action;
            unset($combatant->current_action);
            $combatant->save();
        }

        $livingCombatants = $combatants->where('current_health', '>', '0');
        if ($livingCombatants->count() == 0) {
            $this->setWinner($heroBattle, null);
        } elseif ($livingCombatants->count() == 1) {
            $this->setWinner($heroBattle, $livingCombatants->first());
        }

        if (!$heroBattle->finished) {
            $heroBattle->increment('current_turn');
            if ($heroBattle->allReady()) {
                $this->processTurn($heroBattle);
            }
        }

        return true;
    }

    private function determineAction(HeroCombatant $combatant): string
    {
        $queuedActions = $combatant->actions ?? [];

        if (count($queuedActions) > 0) {
            $limitedActions = $this->heroHelper->getLimitedCombatActions();
            $nextAction = array_shift($queuedActions);
            $combatant->actions = $queuedActions;
            if (!in_array($nextAction, $limitedActions) || $nextAction != $combatant->last_action) {
                return $nextAction;
            }
        }

        switch ($combatant->strategy) {
            case 'aggressive':
                $options = collect(['attack', 'focus', 'counter']);
                if ($combatant->has_focus) {
                    return 'attack';
                }
                return $this->randomAction($options, $combatant->last_action);
            case 'defensive':
                $options = collect(['attack', 'defend', 'recover']);
                if ($combatant->health > ($combatant->current_health - $combatant->defense)) {
                    $options->forget('recover');
                }
                return $this->randomAction($options, $combatant->last_action);
            default:
                $options = collect(['attack', 'defend', 'focus', 'counter', 'recover']);
                if ($combatant->has_focus) {
                    return 'attack';
                }
                if ($combatant->health > ($combatant->current_health - $combatant->defense)) {
                    $options->forget('recover');
                }
                return $this->randomAction($options, $combatant->last_action);
        }
    }

    private function randomAction(Collection $options, ?string $last_action): string
    {
        $limitedActions = collect($this->heroHelper->getLimitedCombatActions());

        foreach ($limitedActions as $action) {
            if ($action == $last_action) {
                $options->forget($action);
            }
        }

        return $options->random();
    }

    private function processAction(HeroCombatant $combatant, HeroCombatant $target): array
    {
        $damage = 0;
        $health = 0;
        $description = '';

        switch ($combatant->current_action) {
            case 'attack':
                $damage = $this->heroCalculator->calculateCombatDamage($combatant, $target);
                $combatant->has_focus = false;
                $evaded = $this->heroCalculator->calculateCombatEvade($target);
                if ($evaded) {
                    $damageEvaded = $damage;
                    $damage = 0;
                    $description = sprintf(
                        "%s deals %s damage, but %s evades.",
                        $combatant->name,
                        $damageEvaded,
                        $target->name
                    );
                } else {
                    $description = sprintf(
                        "%s deals %s damage.",
                        $combatant->name,
                        $damage
                    );
                }
                break;
            case 'defend':
                break;
            case 'focus':
                $combatant->has_focus = true;
                break;
            case 'counter':
                if ($target->current_action == 'attack') {
                    $damage = $this->heroCalculator->calculateCombatDamage($combatant, $target, true);
                    $description = sprintf(
                        "%s deals %s damage.",
                        $combatant->name,
                        $damage
                    );
                }
                break;
            case 'recover':
                $health = $this->heroCalculator->calculateCombatHeal($combatant);
                $description = sprintf(
                    "%s recovers %s health.",
                    $combatant->name,
                    $health
                );
                break;
            default:
                break;
        }

        return [
            'damage' => $damage,
            'health' => $health,
            'description' => $description
        ];
    }

    private function setWinner(HeroBattle $heroBattle, ?HeroCombatant $winner): void
    {
        $heroBattle->winner_combatant_id = $winner ? $winner->id : null;
        $heroBattle->finished = true;
        $heroBattle->save();

        $tournament = $heroBattle->tournaments->first();
        foreach ($heroBattle->combatants as $combatant) {
            $participant = null;
            if ($tournament !== null) {
                $participant = $tournament->participants->where('hero_id', $combatant->hero_id)->first();
            }

            if ($winner == null) {
                $combatant->hero->increment('stat_combat_draws');
                if ($participant !== null) {
                    $participant->increment('draws');
                }
            } elseif ($combatant->id == $winner->id) {
                $combatant->hero->increment('stat_combat_wins');
                if ($participant !== null) {
                    $participant->increment('wins');
                }
            } else {
                $combatant->hero->increment('stat_combat_losses');
                if ($participant !== null) {
                    $participant->increment('losses');
                }
            }
        }
    }
}
