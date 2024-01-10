<?php

namespace OpenDominion\Services\Dominion;

use DB;
use Illuminate\Support\Str;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Helpers\SpellHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Dominion\History;
use OpenDominion\Models\Spell;

class LogParserService
{
    /** @var SpellHelper */
    protected $spellHelper;

    /** @var int */
    protected $currentHour = 0;
    protected $lineNumber = 0;
    protected $spells;
    protected $units;
    protected $errors;

    const ACTIONS = [
        'bank',
        'construction',
        'daily',
        'destruction',
        'draftrate',
        'explore',
        'invest',
        'magic',
        'release',
        'rezone',
        'train'
    ];

    const ATTRIBUTE_MAP = [
        'draftees' => 'military_draftees',
        'Draftees' => 'military_draftees',
        'Spies' => 'military_spies',
        'Archspies' => 'military_assassins',
        'Wizards' => 'military_wizards',
        'Archmages' => 'military_archmages',
        'Fire Spirit' => 'Fire Sprite',
        'Ice Beast' => 'Icebeast',
        'Frost Mage' => 'FrostMage',
        'Voodoo Magi' => 'Voodoo Mage',
        'Mermen' => 'Merman',
        'Sirens' => 'Siren',
        'Alchemies' => 'alchemy',
        'Barracks' => 'barracks',
        'Factories' => 'factory',
        'Guilds' => 'wizard_guild',
        'Lumber Yards' => 'lumberyard',
        'Lumberyards' => 'lumberyard',
        'Masonries' => 'masonry',
        'Smithies' => 'smithy',
        'Ares Call' => 'Ares\' Call',
        'Gaias Blessing' => 'Gaia\'s Blessing',
        'Gaias Watch' => 'Gaia\'s Watch',
        'Miners Sight' => 'Miner\'s Sight'
    ];

    /**
     * LogParserService constructor.
     */
    public function __construct()
    {
        // Helpers
        $this->spellHelper = app(SpellHelper::class);
    }

    public function parseLog(Dominion $dominion, string $log)
    {
        $this->spells = $this->spellHelper->getSpells($dominion->race);
        $this->race = $dominion->race;
        $this->errors = [];

        $actions = array_fill(0, 72, []);
        $lines = explode(PHP_EOL, $log);
        foreach ($lines as $this->lineNumber => $line) {
            $lineValid = false;

            $isHour = preg_match('/Protection Hour: (\d+)/', $line, $hourMatches);
            if ($isHour) {
                if ((int)$hourMatches[1] <= $this->currentHour) {
                    $this->writeError("hour {$this->currentHour} duplicate or out of order");
                } else {
                    $this->currentHour = (int)$hourMatches[1];
                    if ($this->currentHour > 73) break;
                }
                $lineValid = true;
            }

            if (preg_match('/\w/', $line)) {
                foreach ($this::ACTIONS as $action) {
                    $parseFunc = 'parse' . ucfirst($action);
                    $data = $this->$parseFunc($line);
                    if ($data) {
                        $actions[$this->currentHour-1][] = [
                            'line' => $this->lineNumber + 1,
                            'type' => $action,
                            'data' => $data,
                        ];
                        $lineValid = true;
                    }
                }
                if (!$lineValid) {
                    $this->writeError('invalid input');
                }
            }
        }

        if ($this->currentHour < 73) {
            foreach (range($this->currentHour, 72) as $key) {
                unset($actions[$key]);
            }
        }

        return [$this->errors, $actions];
    }

    protected function writeError(string $message)
    {
        $line = $this->lineNumber + 1;
        $this->errors[] = "Line {$line}: {$message}";
    }

    protected function parseBank(string $line)
    {
        if (preg_match('/([\w\s,]*) have been traded for (\d+) (\w+)/', $line, $matches)) {
            if (preg_match_all('/(\d+)\s(\w+)/', $matches[1], $bankMatches)) {
                $bankData = [];
                $target = "resource_$matches[3]";
                foreach ($bankMatches[1] as $idx => $amount) {
                    $tradeAction = [
                        'target' => $target,
                        'amount' => $amount,
                        'source' => "resource_{$bankMatches[2][$idx]}"
                    ];
                    $bankData[] = $tradeAction;
                }
                return $bankData;
            }
        }
        return false;
    }

    protected function parseConstruction(string $line)
    {
        if (preg_match('/Construction of ([\w\s,-]*) started at a cost of/', $line, $matches)) {
            if (preg_match_all('/(-*\d+)\s([\w\s]+)/', $matches[1], $constructMatches)) {
                $constructData = [];
                foreach ($constructMatches[1] as $idx => $amount) {
                    // TODO: validate type
                    $name = $constructMatches[2][$idx];
                    if (isset($this::ATTRIBUTE_MAP[$name])) {
                        $buildingType = $this::ATTRIBUTE_MAP[$name];
                    } else {
                        $buildingType = str_replace(' ', '_', strtolower(rtrim($name, 's')));
                    }
                    $constructData["building_$buildingType"] = (int)$amount;
                }
                return $constructData;
            }
        }
        return false;
    }

    protected function parseDaily(string $line)
    {
        if (preg_match('/You have been awarded with (\d+) (\w+)/', $line, $matches)) {
            if ($matches[2] != 'platinum') {
                return 'land';
            }
            return 'platinum';
        }
        return false;
    }

    protected function parseDestruction(string $line)
    {
        if (preg_match('/Destruction of ([\w\s,-]*) is complete/', $line, $matches)) {
            if (preg_match_all('/(-*\d+)\s([\w\s]+)/', $matches[1], $destroyMatches)) {
                $destroyData = [];
                foreach ($destroyMatches[1] as $idx => $amount) {
                    // TODO: validate type
                    $name = $destroyMatches[2][$idx];
                    if (isset($this::ATTRIBUTE_MAP[$name])) {
                        $buildingType = $this::ATTRIBUTE_MAP[$name];
                    } else {
                        $buildingType = str_replace(' ', '_', strtolower(rtrim($name, 's')));
                    }
                    $destroyData["$buildingType"] = (int)$amount;
                }
                return $destroyData;
            }
        }
        return false;
    }

    protected function parseDraftrate(string $line)
    {
        if (preg_match('/Draftrate changed to (\d+)/', $line, $matches)) {
            return (int)$matches[1];
        }
        return false;
    }

    protected function parseExplore(string $line)
    {
        if (preg_match('/Exploration for ([\w\s,-]*) begun at a cost of/', $line, $matches)) {
            if (preg_match_all('/(-*\d+)\s(\w+)/', $matches[1], $exploreMatches)) {
                $exploreData = [];
                foreach ($exploreMatches[1] as $idx => $amount) {
                    $landType = str_replace(' ', '_', strtolower(rtrim($exploreMatches[2][$idx], 's')));
                    $exploreData["land_$landType"] = (int)$amount;
                }
                return $exploreData;
            }
        }
        return false;
    }

    protected function parseInvest(string $line)
    {
        if (preg_match('/You invested (\d+) (\w+) into (\w+)/', $line, $matches)) {
            $improvement = strtolower($matches[3]);
            return [
                'amount' => $matches[1],
                'resource' => strtolower($matches[2]),
                'improvement' => $improvement
            ];
        }
        return false;
    }

    protected function parseMagic(string $line)
    {
        if (preg_match('/Your wizards successfully cast (.*) at a cost of (\d+) mana/', $line, $matches)) {
            $spellName = trim(str_replace("'", '', $matches[1]));
            if ($spellName == 'Racial Spell') {
                $racialSpell = $this->spellHelper->getSpells($this->race, 'self')->where('races', '!=', null)->first();
                $spell = $this->spells->firstWhere('name', $racialSpell->name);
            } else {
                if (isset($this::ATTRIBUTE_MAP[$spellName])) {
                    $spellName = $this::ATTRIBUTE_MAP[$spellName];
                }
                $spell = $this->spells->firstWhere('name', $spellName);
            }
            if (!$spell) {
                throw new GameException("Spell not found: {$spellName}");
            }
            return $spell->key;
        }
        return false;
    }

    protected function parseRelease(string $line)
    {
        if (preg_match('/You successfully released ([\w\s,]*)/', $line, $matches)) {
            if (preg_match_all('/(\d+)\s([\w\s]+)/', $matches[1], $releaseMatches)) {
                $releaseData = [];
                foreach ($releaseMatches[1] as $idx => $amount) {
                    $name = str_replace(' into the peasantry', '', $releaseMatches[2][$idx]);
                    if (isset($this::ATTRIBUTE_MAP[$name])) {
                        $name = $this::ATTRIBUTE_MAP[$name];
                    }
                    if (Str::startsWith($name, 'military_')) {
                        $attribute = Str::replace('military_', '', $name);
                    } else {
                        $unit = $this->race->units->firstWhere('name', $name);
                        if (!$unit) {
                            throw new GameException("Unit not found for this race: {$name}");
                        }
                        $attribute = "unit{$unit->slot}";
                    }
                    $releaseData[$attribute] = (int)$amount;
                }
                return $releaseData;
            }
        }
        return false;
    }

    protected function parseRezone(string $line)
    {
        if (preg_match('/The changes in land are as following: ([\w\s,-]*)/', $line, $matches)) {
            if (preg_match_all('/(-*\d+)\s(\w+)/', $matches[1], $rezoneMatches)) {
                $rezoneData = [];
                foreach ($rezoneMatches[1] as $idx => $amount) {
                    $landType = str_replace(' ', '_', strtolower(rtrim($rezoneMatches[2][$idx], 's')));
                    if ((int)$amount < 0) {
                        $rezoneData['remove'][$landType] = -(int)$amount;
                    } else {
                        $rezoneData['add'][$landType] = (int)$amount;
                    }
                }
                return $rezoneData;
            }
        }
        return false;
    }

    protected function parseTrain(string $line)
    {
        if (preg_match('/Training of ([\w\s,]*) begun at a cost of ([\w\s,]*)/', $line, $matches)) {
            if (preg_match_all('/(\d+)\s([\w\s]+)/', $matches[1], $trainingMatches)) {
                $trainingData = [];
                foreach ($trainingMatches[1] as $idx => $amount) {
                    $name = $trainingMatches[2][$idx];
                    if (isset($this::ATTRIBUTE_MAP[$name])) {
                        $name = $this::ATTRIBUTE_MAP[$name];
                    }
                    if (Str::startsWith($name, 'military_')) {
                        $attribute = $name;
                    } else {
                        $unit = $this->race->units->firstWhere('name', $name);
                        if (!$unit) {
                            throw new GameException("Unit not found for this race: {$name}");
                        }
                        $attribute = "military_unit{$unit->slot}";
                    }
                    $trainingData[$attribute] = (int)$amount;
                }
                return $trainingData;
            }
        }
        return false;
    }

    public function writeLog(Dominion $dominion) {
        if ($dominion->protection_ticks_remaining == 0 && $dominion->round->hasStarted()) {
            return [];
        }

        $log = ['====== Protection Hour: 1 ======'];
        $protectionHour = 1;
        $events = $dominion->history();

        // Ignore everything prior to latest restart
        $lastRestart = $dominion->history()
            ->where('event', 'restart')
            ->orderByDesc('created_at')
            ->first();
        if ($lastRestart !== null) {
            $events = $events->where('created_at', '>', $lastRestart->created_at);
        }

        foreach ($events->orderBy('created_at')->get() as $history) {
            if ($history->event == 'tick') {
                $protectionHour++;
                if ($protectionHour > 73) {
                    break;
                }
            }
            $log[] = $this->formatHistory($dominion, $history, $protectionHour);
        }

        return $log;
    }

    protected function formatHistory(Dominion $dominion, History $history, int $hour) {
        switch ($history->event) {
            case 'tick':
                return sprintf(
                    '====== Protection Hour: %d ======',
                    $hour
                );

            case 'bank':
                $negative = array_map(
                    function ($value) {
                        return abs($value);
                    },
                    array_filter($history->delta, function ($value) {
                        return $value < 0;
                    }),
                );
                $positive = array_filter($history->delta, function ($value) {
                    return $value > 0;
                });
                return sprintf(
                    '%s have been traded for %s.',
                    dominion_attr_sentence_from_array($negative, true),
                    dominion_attr_sentence_from_array($positive, true)
                );

            case 'construct':
                if (!isset($history->delta['queue']['construction'])) {
                    return '';
                }
                $resources = array_map(
                    function ($value) {
                        return abs($value);
                    },
                    array_filter($history->delta, function ($key) {
                        return Str::startsWith($key, 'resource_');
                    }, ARRAY_FILTER_USE_KEY)
                );
                return sprintf(
                    'Construction of %s started at a cost of %s.',
                    dominion_attr_sentence_from_array($history->delta['queue']['construction'], true),
                    dominion_attr_sentence_from_array($resources, true)
                );

            case 'daily bonus':
                $resources = array_filter($history->delta, function ($key) {
                    return Str::startsWith($key, 'land_') || Str::startsWith($key, 'resource_');
                }, ARRAY_FILTER_USE_KEY);
                return sprintf(
                    'You have been awarded with %s.',
                    dominion_attr_sentence_from_array($resources, true)
                );

            case 'destroy':
                $buildings =  array_map(
                    function ($value) {
                        return abs($value);
                    },
                    array_filter($history->delta, function ($key) {
                        return Str::startsWith($key, 'building_');
                    }, ARRAY_FILTER_USE_KEY)
                );
                return sprintf(
                    'Destruction of %s is complete.',
                    dominion_attr_sentence_from_array($buildings, true)
                );

            case 'change draft rate':
                # TODO: Calculate draftrate from deltas
                return sprintf(
                    'Draftrate changed to %s.',
                    '90%'
                );

            case 'explore':
                if (!isset($history->delta['queue']['exploration'])) {
                    return '';
                }
                $resources = array_map(
                    function ($value) {
                        return abs($value);
                    },
                    array_filter($history->delta, function ($key) {
                        return Str::startsWith($key, 'resource_');
                    }, ARRAY_FILTER_USE_KEY)
                );
                return sprintf(
                    'Exploration for %s begun at a cost of %s.',
                    dominion_attr_sentence_from_array($history->delta['queue']['exploration'], true),
                    dominion_attr_sentence_from_array($resources, true)
                );

            case 'improve':
                $resources = array_map(
                    function ($value) {
                        return abs($value);
                    },
                    array_filter($history->delta, function ($key) {
                        return Str::startsWith($key, 'resource_');
                    }, ARRAY_FILTER_USE_KEY)
                );
                $imps = array_filter($history->delta, function ($key) {
                    return Str::startsWith($key, 'improvement_');
                }, ARRAY_FILTER_USE_KEY);
                return sprintf(
                    'You invested %s into %s.',
                    dominion_attr_sentence_from_array($resources, true),
                    dominion_attr_display(key($imps), true)
                );

            case 'cast spell':
                if (!isset($history->delta['action']) || !isset($history->delta['resource_mana'])) {
                    return '';
                }
                return sprintf(
                    'Your wizards successfully cast %s at a cost of %d mana.',
                    format_string($history->delta['action']),
                    abs(intval($history->delta['resource_mana']))
                );

            case 'release':
                $military = array_map(
                    function ($value) {
                        return abs($value);
                    },
                    array_filter($history->delta, function ($key) {
                        return Str::startsWith($key, 'military_');
                    }, ARRAY_FILTER_USE_KEY)
                );
                $militaryTotal = 0;
                $militaryTransformed = [];
                foreach ($military as $key => $value) {
                    if  (Str::startsWith($key, 'military_unit')) {
                        $unitSlot = substr($key, -1);
                        $unit = $dominion->race->units->where('slot', $unitSlot)->first();
                        $unitName = sprintf(
                            'military_unit_%s',
                            str_replace(' ', '_', strtolower($unit->name))
                        );
                        $militaryTransformed[$unitName] = $value;
                    } else {
                        $militaryTransformed[$key] = $value;
                    }
                    if ($key !== 'military_draftees') {
                        $militaryTotal += $value;
                    }
                }
                if (isset($militaryTransformed['military_draftees'])) {
                    if ($militaryTransformed['military_draftees'] == $militaryTotal) {
                        unset($militaryTransformed['military_draftees']);
                    } else {
                        $militaryTransformed['military_draftees'] = abs(
                            $militaryTransformed['military_draftees'] - $militaryTotal
                        );
                    }
                }
                return sprintf(
                    'You successfully released %s.',
                    dominion_attr_sentence_from_array($militaryTransformed, true)
                );

            case 'rezone':
                $resources = array_map(
                    function ($value) {
                        return abs($value);
                    },
                    array_filter($history->delta, function ($key) {
                        return Str::startsWith($key, 'resource_');
                    }, ARRAY_FILTER_USE_KEY)
                );
                $land = array_filter($history->delta, function ($key) {
                    return Str::startsWith($key, 'land_');
                }, ARRAY_FILTER_USE_KEY);
                return sprintf(
                    'Rezoning begun at a cost of %s. The changes in land are as following: %s',
                    dominion_attr_sentence_from_array($resources, true),
                    dominion_attr_sentence_from_array($land, true)
                );

            case 'train':
                if (!isset($history->delta['queue']['training'])) {
                    return '';
                }
                $military = array_map(
                    function ($value) {
                        return abs($value);
                    },
                    array_filter($history->delta['queue']['training'], function ($key) {
                        return Str::startsWith($key, 'military_');
                    }, ARRAY_FILTER_USE_KEY)
                );
                $militaryTransformed = [];
                foreach ($military as $key => $value) {
                    if  (Str::startsWith($key, 'military_unit')) {
                        $unitSlot = substr($key, -1);
                        $unit = $dominion->race->units->where('slot', $unitSlot)->first();
                        $unitName = sprintf(
                            'military_unit_%s',
                            str_replace(' ', '_', strtolower($unit->name))
                        );
                        $militaryTransformed[$unitName] = $value;
                    } else {
                        $militaryTransformed[$key] = $value;
                    }
                }
                $resources = array_map(
                    function ($value) {
                        return abs($value);
                    },
                    array_filter($history->delta, function ($key) {
                        return Str::startsWith($key, 'resource_');
                    }, ARRAY_FILTER_USE_KEY)
                );
                return sprintf(
                    'Training of %s begun at a cost of %s.',
                    dominion_attr_sentence_from_array($militaryTransformed, true),
                    dominion_attr_sentence_from_array($resources, true),
                );
        }
    }
}
